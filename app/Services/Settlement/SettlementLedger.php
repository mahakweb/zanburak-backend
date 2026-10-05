<?php

namespace App\Services\Settlement;

use App\Models\Course;
use App\Models\Path;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Plan;
use App\Models\Settlement;
use App\Models\User;
use App\Services\Bank\IranianBankInspector;
use App\Services\Security\ContentScope;
use App\Support\IranianBanks;
use App\Support\SettlementStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SettlementLedger
{
    public function __construct(private SettlementSummary $summary)
    {
    }

    public function teacherConstraint(User $user): ?int
    {
        $constraint = $this->summary->teacherConstraint($user);

        return $constraint === -1 ? -1 : $constraint;
    }

    public function query(User $user): Builder
    {
        $query = $user->contentScope()->applyToPayments(Payment::query());
        $teacherId = $this->teacherConstraint($user);
        if ($teacherId === -1) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public function applyFilters(Builder $query, array $filters, ?int $teacherId): Builder
    {
        $status = $filters['settlement_status'] ?? 'all';
        if ($status === 'settled') {
            $this->whereSettled($query, $teacherId, true);
        } elseif ($status === 'unsettled') {
            $this->whereSettled($query, $teacherId, false);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('uuid', 'like', "%{$search}%")
                    ->orWhere('reference_id', 'like', "%{$search}%")
                    ->orWhere('tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $user) use ($search) {
                        $user->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'amount_high' => $query->orderByDesc('amount')->orderByDesc('id'),
            'amount_low' => $query->orderBy('amount')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return $query;
    }

    public function stats(Builder $query, ?int $teacherId): array
    {
        $base = clone $query;
        $base->reorder();
        $settled = clone $base;
        $unsettled = clone $base;
        $this->whereSettled($settled, $teacherId, true);
        $this->whereSettled($unsettled, $teacherId, false);

        return [
            'total_payments' => (clone $base)->count(),
            'settled_payments' => (clone $settled)->count(),
            'unsettled_payments' => (clone $unsettled)->count(),
            'settled_amount' => $this->shareTotal($settled, $teacherId),
            'unsettled_amount' => $this->shareTotal($unsettled, $teacherId),
        ];
    }

    public function row(Payment $payment, ?int $teacherId): array
    {
        $items = $this->visibleItems($payment, $teacherId);
        $settlements = $this->activeSettlements($payment, $items);
        $settled = $this->isSettled($payment, $items, $settlements);
        $latest = $settlements->sortByDesc(fn (Settlement $settlement) => optional($settlement->paid_at)?->timestamp ?? 0)->first();

        return [
            'id' => $payment->id,
            'uuid' => $payment->uuid,
            'reference_id' => $payment->reference_id,
            'amount' => (int) $payment->amount,
            'settle_amount' => $this->settleAmount($payment, $items),
            'discount_amount' => $this->discountAmount($payment, $items),
            'discount_code' => $this->discountCode($payment, $items),
            'status' => (bool) $payment->status,
            'paid_at' => optional($payment->paid_at)?->toIso8601String(),
            'created_at' => optional($payment->created_at)?->toIso8601String(),
            'user' => $payment->user ? [
                'id' => $payment->user->id,
                'first_name' => $payment->user->first_name,
                'last_name' => $payment->user->last_name,
                'email' => $payment->user->email,
                'profile_pic' => $payment->user->profile_pic,
            ] : null,
            'settlement_status' => $settled ? 'settled' : 'unsettled',
            'settled_at' => $settled ? optional($latest?->paid_at)?->toIso8601String() : null,
            'settled_by' => $settled ? $this->personName($latest?->payer) : null,
            'tracking_number' => $settlements->pluck('tracking_number')->filter()->unique()->implode('، ') ?: null,
            'has_receipt' => $settlements->contains(fn (Settlement $settlement) => (bool) $settlement->current_receipt_id),
            'payouts' => $settled ? $this->payoutsOf($settlements, false) : [],
        ];
    }

    public function detail(User $viewer, Payment $payment): array
    {
        $teacherId = $this->teacherConstraint($viewer);
        $items = $this->visibleItems($payment, $teacherId === -1 ? -1 : $teacherId);
        $settlements = $teacherId === -1 ? collect() : $this->activeSettlements($payment, $items);
        $settled = $teacherId !== -1 && $this->isSettled($payment, $items, $settlements);
        $scope = $viewer->contentScope();
        $fullItems = $scope->viewScope('payments') === ContentScope::ANY
            || $scope->viewScope('settlements') === ContentScope::ANY
            || $viewer->isSuperUser();

        $shown = $fullItems ? $payment->items : $items;

        return [
            'payment' => $this->row($payment, $teacherId === -1 ? null : $teacherId),
            'description' => $payment->description,
            'driver' => $payment->driver,
            'discount_amount' => (int) ($payment->discount_amount ?? 0),
            'discount_code' => $payment->discount_code,
            'wallet_paid_amount' => (int) ($payment->wallet_paid_amount ?? 0),
            'gateway_paid_amount' => (int) ($payment->gateway_paid_amount ?? 0),
            'items' => $shown->map(fn (PaymentItem $item) => [
                'id' => $item->id,
                'kind' => $this->itemKind($item),
                'title' => $this->itemTitle($item),
                'price' => (int) $item->price,
                'discount_amount' => (int) ($item->discount_amount ?? 0),
                'discount_code' => $item->discount_code ?: $payment->discount_code,
                'amount' => TeacherShare::amount($item),
                'settled' => $this->itemIsSettled($item),
            ])->values(),
            'settlements' => $settlements->map(function (Settlement $settlement) use ($viewer) {
                return [
                    'uuid' => $settlement->uuid,
                    'status' => $settlement->status,
                    'paid_at' => optional($settlement->paid_at)?->toIso8601String(),
                    'settled_by' => $this->personName($settlement->payer),
                    'tracking_number' => $settlement->tracking_number,
                    'description' => $settlement->description,
                    'payout' => $this->payoutsOf(collect([$settlement]), true)[0] ?? null,
                    'receipt' => $this->receiptPayload($viewer, $settlement),
                ];
            })->values(),
            'settlement_status' => $settled ? 'settled' : 'unsettled',
            'limited_items' => ! $fullItems,
            'can_change' => $scope->canAction('settlements', 'change_status'),
            'can_upload' => $scope->canAction('settlements', 'upload_receipt'),
            'can_view_receipt' => $scope->canAction('settlements', 'view_receipt'),
        ];
    }

    private function payoutsOf(Collection $settlements, bool $full): array
    {
        $inspector = app(IranianBankInspector::class);

        return $settlements->map(function (Settlement $settlement) use ($full, $inspector) {
            if (! $settlement->payout_kind || ! $settlement->payout_number) {
                return null;
            }
            $digits = preg_replace('/\s+/', '', (string) $settlement->payout_number) ?: '';
            $number = $full
                ? $inspector->format($settlement->payout_kind, $digits)
                : (strlen($digits) <= 8 ? $digits : substr($digits, 0, 4).'···'.substr($digits, -4));

            return [
                'kind' => $settlement->payout_kind,
                'kind_label' => match ($settlement->payout_kind) {
                    'card' => 'کارت',
                    'account' => 'حساب',
                    default => 'شبا',
                },
                'number' => $number,
                'holder' => $settlement->account_holder,
                'bank' => IranianBanks::find($settlement->payout_bank_code),
            ];
        })->filter()->unique(fn (array $row) => $row['kind'].'|'.$row['number'])->values()->all();
    }

    public function visibleItems(Payment $payment, ?int $teacherId): Collection
    {
        $items = $payment->items ?? collect();
        if ($teacherId === -1) {
            return collect();
        }
        if ($teacherId) {
            return $items->filter(function (PaymentItem $item) use ($teacherId) {
                return TeacherShare::isCourse($item)
                    && $item->payable instanceof Course
                    && (int) $item->payable->teacher_id === $teacherId;
            })->values();
        }

        return $items->values();
    }

    public function applyStatus(Builder $query, ?int $teacherId, bool $settled): void
    {
        $this->whereSettled($query, $teacherId, $settled);
    }

    private function whereSettled(Builder $query, ?int $teacherId, bool $settled): void
    {
        $courseType = Course::class;
        $settledStatus = SettlementStatus::SETTLED;

        $query->where(function (Builder $outer) use ($teacherId, $settled, $courseType, $settledStatus) {
            $method = $settled ? 'where' : 'orWhere';
            if (! $settled) {
                $outer->where(function (Builder $missing) use ($teacherId, $courseType, $settledStatus) {
                    $this->missingSettledItem($missing, $teacherId, $courseType, $settledStatus);
                });
                if (! $teacherId) {
                    $outer->orWhere(function (Builder $empty) use ($settledStatus) {
                        $empty->whereDoesntHave('items')
                            ->whereDoesntHave('coveredSettlement', function (Builder $covered) use ($settledStatus) {
                                $covered->where('status', $settledStatus);
                            });
                    });
                }

                return;
            }

            $outer->where(function (Builder $covered) use ($teacherId, $courseType, $settledStatus) {
                $covered->whereHas('items', function (Builder $items) use ($teacherId, $courseType) {
                    if ($teacherId) {
                        $items->where('payable_type', $courseType)
                            ->whereIn('payable_id', Course::query()->where('teacher_id', $teacherId)->select('id'));
                    }
                })->whereNotExists(function ($sub) use ($teacherId, $courseType, $settledStatus) {
                    $this->unsettledItemSql($sub, $teacherId, $courseType, $settledStatus);
                });
                if (! $teacherId) {
                    $covered->orWhere(function (Builder $empty) use ($settledStatus) {
                        $empty->whereDoesntHave('items')
                            ->whereHas('coveredSettlement', function (Builder $row) use ($settledStatus) {
                                $row->where('status', $settledStatus);
                            });
                    });
                }
            });
            unset($method);
        });
    }

    private function missingSettledItem(Builder $query, ?int $teacherId, string $courseType, string $settledStatus): void
    {
        $query->whereExists(function ($sub) use ($teacherId, $courseType, $settledStatus) {
            $this->unsettledItemSql($sub, $teacherId, $courseType, $settledStatus);
        });
    }

    private function unsettledItemSql($sub, ?int $teacherId, string $courseType, string $settledStatus): void
    {
        $sub->selectRaw('1')
            ->from('payment_items as pi')
            ->whereColumn('pi.payment_id', 'payments.id')
            ->whereNotExists(function ($settled) use ($settledStatus) {
                $settled->selectRaw('1')
                    ->from('settlement_items as si')
                    ->join('settlements as s', 's.id', '=', 'si.settlement_id')
                    ->whereColumn('si.active_payment_item_id', 'pi.id')
                    ->where('s.status', $settledStatus);
            });

        if ($teacherId) {
            $sub->where('pi.payable_type', $courseType)
                ->whereIn('pi.payable_id', Course::query()->where('teacher_id', $teacherId)->select('id'));
        }
    }

    private function activeSettlements(Payment $payment, Collection $items): Collection
    {
        $fromItems = $items
            ->map(fn (PaymentItem $item) => $item->activeSettlementItem?->settlement)
            ->filter()
            ->keyBy('id');

        $covered = $payment->coveredSettlement;
        if ($covered && $items->isEmpty()) {
            $fromItems->put($covered->id, $covered);
        }

        return $fromItems->values();
    }

    private function isSettled(Payment $payment, Collection $items, Collection $settlements): bool
    {
        if ($items->isEmpty()) {
            $covered = $payment->coveredSettlement;

            return $covered && $covered->status === SettlementStatus::SETTLED;
        }

        return $items->every(fn (PaymentItem $item) => $this->itemIsSettled($item));
    }

    private function itemIsSettled(PaymentItem $item): bool
    {
        $settlement = $item->activeSettlementItem?->settlement;

        return $settlement && $settlement->status === SettlementStatus::SETTLED;
    }

    private function settleAmount(Payment $payment, Collection $items): int
    {
        if ($items->isNotEmpty()) {
            return (int) $items->sum(fn (PaymentItem $item) => TeacherShare::amount($item));
        }

        $base = (int) ($payment->base_amount ?? 0);

        return $base > 0 ? $base : (int) $payment->amount;
    }

    private function discountAmount(Payment $payment, Collection $items): int
    {
        if ($items->isNotEmpty()) {
            return (int) $items->sum(fn (PaymentItem $item) => (int) ($item->discount_amount ?? 0));
        }

        return (int) ($payment->discount_amount ?? 0);
    }

    private function discountCode(Payment $payment, Collection $items): ?string
    {
        $codes = $items->pluck('discount_code')->filter()->unique()->values();
        if ($codes->isNotEmpty()) {
            return $codes->implode('، ');
        }

        return $payment->discount_code ?: null;
    }

    private function shareTotal(Builder $paymentQuery, ?int $teacherId): int
    {
        $ids = (clone $paymentQuery)->reorder()->select('payments.id');
        $items = PaymentItem::query()->whereIn('payment_id', $ids);
        if ($teacherId) {
            $items->where('payable_type', Course::class)
                ->whereIn('payable_id', Course::query()->where('teacher_id', $teacherId)->select('id'));
        }

        $sum = (int) $items->sum(DB::raw(TeacherShare::SQL));
        if ($teacherId) {
            return $sum;
        }

        $empty = (int) (clone $paymentQuery)->reorder()
            ->whereDoesntHave('items')
            ->sum(DB::raw('COALESCE(NULLIF(base_amount, 0), amount, 0)'));

        return $sum + $empty;
    }

    private function itemKind(PaymentItem $item): string
    {
        return match ($item->payable_type) {
            Course::class => 'دوره',
            Path::class => 'مسیر',
            Plan::class => 'پلن',
            default => 'سایر',
        };
    }

    private function itemTitle(PaymentItem $item): string
    {
        $payable = $item->payable;

        return $payable && isset($payable->title) ? (string) $payable->title : 'مورد پرداخت';
    }

    private function personName(?User $user): ?string
    {
        if (! $user) {
            return null;
        }

        $name = trim($user->first_name.' '.$user->last_name);

        return $name !== '' ? $name : $user->username;
    }

    private function receiptPayload(User $viewer, Settlement $settlement): ?array
    {
        $receipt = $settlement->currentReceipt;
        if (! $receipt || $receipt->deleted_at) {
            return null;
        }
        if (! $viewer->contentScope()->canAction('settlements', 'view_receipt', $settlement)) {
            return null;
        }

        return [
            'settlement_uuid' => $settlement->uuid,
            'mime' => $receipt->mime,
            'original_name' => $receipt->original_name,
        ];
    }
}
