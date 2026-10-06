<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\SettlementException;
use App\Http\Controllers\Controller;
use App\Models\Settlement;
use App\Models\User;
use App\Services\Security\ContentScope;
use App\Models\Course;
use App\Models\Payment;
use App\Services\Settlement\SettlementLedger;
use App\Services\Settlement\SettlementPeriod;
use App\Services\Settlement\SettlementPresenter;
use App\Services\Settlement\SettlementService;
use App\Services\Settlement\SettlementSummary;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Support\SettlementStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettlementController extends Controller
{
    public function __construct(
        private SettlementService $settlements,
        private SettlementSummary $summary,
        private SettlementLedger $ledger,
    ) {
    }

    public function summary(Request $request)
    {
        $user = $this->financeUser();
        [$from, $to, $preset] = SettlementPeriod::fromRequest($request, true);

        return response()->json([
            'message' => 'Success',
            'preset' => $preset,
            'summary' => $this->summary->dashboard($user, $from, $to),
        ]);
    }

    public function index(Request $request)
    {
        $user = $this->financeUser();
        $teacherId = $this->summary->teacherConstraint($user);
        $query = Settlement::query()
            ->with([
                'teacher:id,first_name,last_name,username',
                'creator:id,first_name,last_name',
                'payer:id,first_name,last_name',
                'currentReceipt',
            ])
            ->withCount('items');

        if ($teacherId === -1) {
            $query->whereRaw('1 = 0');
        } elseif ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }

        if ($request->filled('teacher_id')) {
            $requested = (int) $request->input('teacher_id');
            if ($teacherId && $requested !== $teacherId) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('teacher_id', $requested);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('paid_from')) {
            $query->whereDate('paid_at', '>=', $request->input('paid_from'));
        }
        if ($request->filled('paid_to')) {
            $query->whereDate('paid_at', '<=', $request->input('paid_to'));
        }
        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', (int) $request->input('amount_min'));
        }
        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', (int) $request->input('amount_max'));
        }
        if ($request->filled('created_by')) {
            $query->where('created_by', (int) $request->input('created_by'));
        }
        if ($request->filled('paid_by')) {
            $query->where('paid_by', (int) $request->input('paid_by'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($inner) use ($search) {
                $inner->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('uuid', 'like', "%{$search}%")
                    ->orWhereHas('teacher', function ($teacher) use ($search) {
                        $teacher->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%");
                    });
                if (ctype_digit((string) $search)) {
                    $inner->orWhere('id', (int) $search);
                }
            });
        }

        $query->orderByDesc('id');
        $page = $query->paginate((int) $request->input('perPage', 20));
        $presenter = new SettlementPresenter($user);

        return response()->json([
            'message' => 'Success',
            'settlements' => collect($page->items())->map(fn (Settlement $settlement) => $presenter->listItem($settlement))->values(),
            'pagination' => $this->pagination($page),
        ]);
    }

    public function ledger(Request $request)
    {
        $user = $this->financeUser();
        $teacherId = $this->positiveTeacher($user);
        $filters = $request->only(['settlement_status', 'date_from', 'date_to', 'search', 'sort']);

        $statQuery = $this->ledger->query($user);
        $this->ledger->applyFilters($statQuery, array_merge($filters, ['settlement_status' => 'all']), $teacherId);
        $stats = $this->ledger->stats($statQuery, $teacherId);

        $query = $this->ledgerQuery($user);
        $this->ledger->applyFilters($query, $filters, $teacherId);
        $page = $query->paginate(
            max(1, min(100, (int) $request->input('perPage', 20))),
            ['*'],
            'page',
            max(1, (int) $request->input('page', 1))
        );

        return response()->json([
            'message' => 'Success',
            'payments' => collect($page->items())->map(fn (Payment $payment) => $this->ledger->row($payment, $teacherId))->values(),
            'pagination' => $this->pagination($page),
            'stats' => $stats,
            'can_change' => $user->contentScope()->canAction('settlements', 'change_status'),
            'can_upload' => $user->contentScope()->canAction('settlements', 'upload_receipt'),
        ]);
    }

    public function payment(string $payment)
    {
        $user = $this->financeUser();
        $row = $this->ledgerQuery($user)->where('uuid', $payment)->firstOrFail();

        return response()->json([
            'message' => 'Success',
            'payment' => $this->ledger->detail($user, $row),
        ]);
    }

    public function mark(Request $request)
    {
        $user = $this->manager($request, 'change_status');
        if ($request->hasFile('receipt')) {
            $user->contentScope()->authorizeAction('settlements', 'upload_receipt', null, 'اجازه آپلود رسید را ندارید.');
        }

        $data = $request->validate([
            'payment_ids' => 'required|array|min:1|max:100',
            'payment_ids.*' => 'integer',
            'status' => 'required|in:settled,unsettled',
            'tracking_number' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:2000',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:8192',
            'payouts' => 'nullable|array|max:20',
            'payouts.*.user_id' => 'required|integer',
            'payouts.*.bank_account_id' => 'required|integer',
        ]);

        $teacherId = $this->summary->teacherConstraint($user);
        if ($teacherId === -1) {
            abort(403, 'اجازه تغییر تسویه را ندارید.');
        }

        return $this->attempt(function () use ($user, $data, $request, $teacherId) {
            if ($data['status'] === 'settled') {
                if (! $request->hasFile('receipt')) {
                    throw new SettlementException('تصویر رسید الزامی است.');
                }
                $user->contentScope()->authorizeAction('settlements', 'upload_receipt', null, 'اجازه آپلود رسید را ندارید.');
                $recipients = $this->settlements->payoutRecipients($user, $data['payment_ids'], $teacherId);
                $chosen = collect($data['payouts'] ?? [])->keyBy(fn ($row) => (int) ($row['user_id'] ?? 0));
                if ($recipients === []) {
                    throw new SettlementException('برای این پرداخت حساب بانکی پیدا نشد.');
                }
                foreach ($recipients as $person) {
                    if (! $chosen->has((int) $person['user_id'])) {
                        throw new SettlementException('برای '.$person['name'].' یک شبا، کارت یا حساب انتخاب کنید.');
                    }
                }
            }

            $result = $this->settlements->markPayments(
                $user,
                $data['payment_ids'],
                $data['status'],
                [
                    'tracking_number' => $data['tracking_number'] ?? null,
                    'description' => $data['description'] ?? null,
                    'payouts' => $data['payouts'] ?? [],
                ],
                $request->file('receipt'),
                $teacherId
            );

            $message = $data['status'] === 'settled' ? 'پرداخت‌های انتخاب‌شده تسویه شد.' : 'تسویه پرداخت‌های انتخاب‌شده برداشته شد.';
            if ($result['skipped'] !== []) {
                $message .= ' '.implode(' ', $result['skipped']);
            }

            return response()->json([
                'message' => $message,
                'updated' => $result['updated'],
                'skipped' => $result['skipped'],
            ]);
        });
    }

    public function payoutOptions(Request $request)
    {
        $user = $this->manager($request, 'change_status');
        $data = $request->validate([
            'payment_ids' => 'required|array|min:1|max:100',
            'payment_ids.*' => 'integer',
        ]);
        $teacherId = $this->summary->teacherConstraint($user);
        if ($teacherId === -1) {
            abort(403, 'اجازه تغییر تسویه را ندارید.');
        }

        return response()->json([
            'recipients' => $this->settlements->payoutRecipients($user, $data['payment_ids'], $teacherId),
            'share' => $this->settlements->sharePreview($user, $data['payment_ids'], $teacherId),
        ]);
    }

    public function preview(Request $request)
    {
        $user = $this->manager($request, 'create');
        $payload = $this->selection($request);

        return $this->attempt(fn () => response()->json([
            'message' => 'Success',
            'preview' => $this->settlements->preview($user, $payload['payment_ids'], $payload['payment_item_ids']),
        ]));
    }

    public function store(Request $request)
    {
        $user = $this->manager($request, 'create');
        $data = $this->validatedSettlement($request);
        $data['idempotency_key'] = $request->header('Idempotency-Key') ?: ($data['idempotency_key'] ?? null);

        return $this->attempt(function () use ($user, $data) {
            $result = $this->settlements->create($user, $data);
            $presenter = new SettlementPresenter($user);

            return response()->json([
                'message' => $result['replayed'] ? 'این تسویه قبلاً ثبت شده است.' : 'تسویه ثبت شد.',
                'replayed' => $result['replayed'],
                'settlements' => $result['settlements']->map(fn (Settlement $settlement) => $presenter->listItem($settlement))->values(),
            ], $result['replayed'] ? 200 : 201);
        });
    }

    public function show(Settlement $settlement)
    {
        $user = $this->financeUser();
        $this->assertVisible($user, $settlement);
        $settlement->load([
            'teacher:id,first_name,last_name,username',
            'creator:id,first_name,last_name',
            'payer:id,first_name,last_name',
            'currentReceipt',
            'items.payment.user:id,first_name,last_name',
            'items.paymentItem.payable',
            'audits.user:id,first_name,last_name',
        ]);

        return response()->json([
            'message' => 'Success',
            'settlement' => (new SettlementPresenter($user))->detail($settlement),
        ]);
    }

    public function update(Request $request, Settlement $settlement)
    {
        $user = $this->manager($request, 'update');
        $this->assertVisible($user, $settlement);
        $data = $request->validate([
            'tracking_number' => 'nullable|string|max:120',
            'paid_at' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
            'iban' => 'nullable|string|max:34',
            'account_holder' => 'nullable|string|max:120',
        ]);

        return $this->attempt(function () use ($user, $settlement, $data) {
            $updated = $this->settlements->update($user, $settlement, $data);

            return response()->json([
                'message' => 'اطلاعات تسویه ذخیره شد.',
                'settlement' => (new SettlementPresenter($user))->listItem($updated->load(['teacher', 'creator', 'payer', 'currentReceipt'])),
            ]);
        });
    }

    public function changeStatus(Request $request, Settlement $settlement)
    {
        $user = $this->manager($request, 'change_status');
        $this->assertVisible($user, $settlement);
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', SettlementStatus::ALL),
        ]);

        return $this->attempt(function () use ($user, $settlement, $data) {
            $updated = $this->settlements->changeStatus($user, $settlement, $data['status']);

            return response()->json([
                'message' => 'وضعیت تسویه تغییر کرد.',
                'settlement' => (new SettlementPresenter($user))->listItem($updated->load(['teacher', 'creator', 'payer', 'currentReceipt'])),
            ]);
        });
    }

    public function uploadReceipt(Request $request, Settlement $settlement)
    {
        $user = $this->manager($request, 'upload_receipt');
        $this->assertVisible($user, $settlement);
        $request->validate([
            'receipt' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:8192',
        ]);

        return $this->attempt(function () use ($user, $settlement, $request) {
            $updated = $this->settlements->storeReceipt($user, $settlement, $request->file('receipt'));

            return response()->json([
                'message' => 'رسید ذخیره شد.',
                'settlement' => (new SettlementPresenter($user))->listItem($updated->load(['teacher', 'creator', 'payer', 'currentReceipt'])),
            ]);
        });
    }

    public function deleteReceipt(Settlement $settlement)
    {
        $user = $this->manager(request(), 'delete_receipt');
        $this->assertVisible($user, $settlement);

        return $this->attempt(function () use ($user, $settlement) {
            $updated = $this->settlements->deleteReceipt($user, $settlement);

            return response()->json([
                'message' => 'رسید از تسویه برداشته شد. فایل قبلی برای حسابرسی نگه داشته شده است.',
                'settlement' => (new SettlementPresenter($user))->listItem($updated->load(['teacher', 'creator', 'payer', 'currentReceipt'])),
            ]);
        });
    }

    public function receipt(Settlement $settlement)
    {
        $user = $this->financeUser();
        $this->assertVisible($user, $settlement);
        $user->contentScope()->authorizeAction('settlements', 'view_receipt', $settlement, 'اجازه مشاهده این رسید را ندارید.');

        $receipt = $settlement->currentReceipt;
        if (! $receipt || $receipt->deleted_at || ! Storage::disk($receipt->disk)->exists($receipt->path)) {
            abort(404, 'رسید پیدا نشد.');
        }

        return Storage::disk($receipt->disk)->response(
            $receipt->path,
            $receipt->original_name ?: basename($receipt->path),
            [
                'Content-Type' => $receipt->mime ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
            request()->boolean('download') ? 'attachment' : 'inline'
        );
    }

    public function audits(Settlement $settlement)
    {
        $user = $this->financeUser();
        $this->assertVisible($user, $settlement);
        $settlement->load('audits.user:id,first_name,last_name');
        $presenter = new SettlementPresenter($user);

        return response()->json([
            'message' => 'Success',
            'audits' => $settlement->audits->map(fn ($audit) => $presenter->audit($audit))->values(),
        ]);
    }

    public function teachers(Request $request)
    {
        $user = $this->financeUser();
        if ($this->summary->teacherConstraint($user) === -1 && ! $user->isSuperUser()) {
            abort(403, 'اجازه مشاهده وضعیت مدرسین را ندارید.');
        }

        $page = $this->summary->teachers($user, $request);

        return response()->json([
            'message' => 'Success',
            'teachers' => collect($page->items())->map(fn ($row) => $this->summary->mapTeacher($row))->values(),
            'pagination' => $this->pagination($page),
        ]);
    }

    public function teacher(User $teacher)
    {
        $user = $this->financeUser();

        return response()->json([
            'message' => 'Success',
            'teacher' => $this->summary->teacher($user, (int) $teacher->id),
        ]);
    }

    private function positiveTeacher(User $user): ?int
    {
        $teacherId = $this->ledger->teacherConstraint($user);

        return $teacherId && $teacherId > 0 ? $teacherId : null;
    }

    private function ledgerQuery(User $user)
    {
        return $this->ledger->query($user)->with([
            'user:id,first_name,last_name,username,email,profile_pic',
            'items.payable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Course::class => ['teacher:id,first_name,last_name,username'],
                ]);
            },
            'items.activeSettlementItem.settlement.payer:id,first_name,last_name,username',
            'items.activeSettlementItem.settlement.currentReceipt',
            'coveredSettlement.payer:id,first_name,last_name,username',
            'coveredSettlement.currentReceipt',
        ]);
    }

    private function financeUser(): User
    {
        $user = auth()->user();
        $scope = $user->contentScope();
        $can = $scope->viewScope('payments') !== ContentScope::NONE
            || $scope->viewScope('settlements') !== ContentScope::NONE;
        if (! $can) {
            abort(403, 'اجازه مشاهده اطلاعات مالی را ندارید.');
        }

        return $user;
    }

    private function manager(Request $request, string $action): User
    {
        $user = $this->financeUser();
        $user->contentScope()->authorizeAction('settlements', $action, null, 'اجازه این عملیات مالی را ندارید.');

        return $user;
    }

    private function assertVisible(User $user, Settlement $settlement): void
    {
        $constraint = $this->summary->teacherConstraint($user);
        if ($constraint === -1 || ($constraint && (int) $settlement->teacher_id !== $constraint)) {
            abort(403, 'به این تسویه دسترسی ندارید.');
        }

        if (! $user->contentScope()->canAction('settlements', 'view', $settlement)
            && $user->contentScope()->viewScope('payments') === ContentScope::NONE) {
            abort(403, 'به این تسویه دسترسی ندارید.');
        }
    }

    private function selection(Request $request): array
    {
        return $request->validate([
            'payment_ids' => 'array',
            'payment_ids.*' => 'integer',
            'payment_item_ids' => 'array',
            'payment_item_ids.*' => 'integer',
        ]);
    }

    private function validatedSettlement(Request $request): array
    {
        return $request->validate([
            'payment_ids' => 'array',
            'payment_ids.*' => 'integer',
            'payment_item_ids' => 'array',
            'payment_item_ids.*' => 'integer',
            'status' => 'nullable|in:pending,processing,settled',
            'tracking_number' => 'nullable|string|max:120',
            'paid_at' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
            'iban' => 'nullable|string|max:34',
            'account_holder' => 'nullable|string|max:120',
            'idempotency_key' => 'nullable|string|max:80',
        ]);
    }

    private function attempt(callable $callback)
    {
        try {
            return $callback();
        } catch (SettlementException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }
    }

    private function pagination($page): array
    {
        return [
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'from' => $page->firstItem(),
            'to' => $page->lastItem(),
        ];
    }
};
