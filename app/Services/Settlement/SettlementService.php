<?php

namespace App\Services\Settlement;

use App\Events\Settlement\SettlementChanged;
use App\Exceptions\SettlementException;
use App\Models\Course;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Settlement;
use App\Models\SettlementAudit;
use App\Models\SettlementItem;
use App\Models\SettlementReceipt;
use App\Models\User;
use App\Models\UserBankAccount;
use App\Notifications\Payment\SettlementPaidNotification;
use App\Services\Bank\IranianBankInspector;
use App\Support\IranianBanks;
use App\Support\SettlementStatus;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettlementService
{
    public function preview(User $actor, array $paymentIds, array $paymentItemIds): array
    {
        $items = $this->loadCandidates($actor, $paymentIds, $paymentItemIds);
        [$groups, $blocked] = $this->partition($items);

        return [
            'groups' => $groups->map(fn (Collection $group) => $this->groupPayload($group))->values()->all(),
            'blocked' => $blocked,
            'can_create' => $groups->isNotEmpty(),
        ];
    }

    public function create(User $actor, array $input): array
    {
        $paymentIds = array_values(array_filter((array) ($input['payment_ids'] ?? [])));
        $paymentItemIds = array_values(array_filter((array) ($input['payment_item_ids'] ?? [])));
        $status = $input['status'] ?? SettlementStatus::PENDING;
        if (! in_array($status, [SettlementStatus::PENDING, SettlementStatus::PROCESSING, SettlementStatus::SETTLED], true)) {
            throw new SettlementException('وضعیت اولیه تسویه معتبر نیست.');
        }

        $idempotencyKey = trim((string) ($input['idempotency_key'] ?? ''));
        if ($idempotencyKey !== '') {
            $existing = $this->findIdempotent($actor, $idempotencyKey);
            if ($existing->isNotEmpty()) {
                return [
                    'settlements' => $existing,
                    'replayed' => true,
                ];
            }
        }

        $created = DB::transaction(function () use ($actor, $paymentIds, $paymentItemIds, $status, $input, $idempotencyKey) {
            $candidates = $this->loadCandidates($actor, $paymentIds, $paymentItemIds);
            $locked = PaymentItem::query()
                ->whereIn('id', $candidates->pluck('id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $locked->load(['payment', 'payable']);
            [$groups, $blocked] = $this->partition($locked, true);
            $hardBlocked = array_values(array_filter($blocked, function (array $row) {
                return ! str_contains($row['message'], 'قابل تسویه نیست')
                    && ! str_contains($row['message'], 'صفر است');
            }));
            if ($hardBlocked !== []) {
                throw new SettlementException($hardBlocked[0]['message'], 409);
            }
            if ($groups->isEmpty()) {
                $reason = $blocked[0]['message'] ?? 'تراکنش قابل تسویه‌ای انتخاب نشده است.';
                throw new SettlementException($reason, 422);
            }

            $settlements = collect();
            foreach ($groups as $teacherId => $group) {
                $settlements->push($this->persistGroup(
                    $actor,
                    (int) $teacherId,
                    $group,
                    $status,
                    $input,
                    $idempotencyKey
                ));
            }

            return $settlements;
        });

        foreach ($created as $settlement) {
            $this->afterCommit($settlement, $actor, $settlement->status === SettlementStatus::SETTLED);
        }

        return [
            'settlements' => $created,
            'replayed' => false,
        ];
    }

    public function update(User $actor, Settlement $settlement, array $input): Settlement
    {
        $changes = [];
        $payload = [];
        foreach (['tracking_number', 'description', 'iban', 'account_holder'] as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }
            $value = $input[$field];
            $value = $value === '' ? null : $value;
            if ($settlement->{$field} !== $value) {
                $changes[$field] = ['from' => $settlement->{$field}, 'to' => $value];
                $payload[$field] = $value;
            }
        }

        if (array_key_exists('paid_at', $input)) {
            $paidAt = $input['paid_at'] ? now()->parse($input['paid_at']) : null;
            $current = optional($settlement->paid_at)?->toDateTimeString();
            $next = $paidAt?->toDateTimeString();
            if ($current !== $next) {
                $changes['paid_at'] = ['from' => $current, 'to' => $next];
                $payload['paid_at'] = $paidAt;
            }
        }

        if ($payload === []) {
            return $settlement;
        }

        $settlement->update($payload);
        $this->audit($settlement, $actor, 'updated', null, null, $changes);
        $this->afterCommit($settlement->fresh(), $actor, false);

        return $settlement->fresh();
    }

    public function changeStatus(User $actor, Settlement $settlement, string $status): Settlement
    {
        if (! in_array($status, SettlementStatus::ALL, true)) {
            throw new SettlementException('وضعیت تسویه معتبر نیست.');
        }

        if ($settlement->status === $status) {
            return $settlement;
        }

        if (! SettlementStatus::canTransition($settlement->status, $status)) {
            throw new SettlementException('تغییر وضعیت از «'.SettlementStatus::label($settlement->status).'» به «'.SettlementStatus::label($status).'» مجاز نیست.');
        }

        $updated = DB::transaction(function () use ($actor, $settlement, $status) {
            $locked = Settlement::query()->whereKey($settlement->id)->lockForUpdate()->firstOrFail();
            if (! SettlementStatus::canTransition($locked->status, $status)) {
                throw new SettlementException('وضعیت این تسویه هم‌زمان تغییر کرده است.', 409);
            }

            $from = $locked->status;
            $payload = ['status' => $status];
            if ($status === SettlementStatus::SETTLED) {
                $payload['paid_at'] = $locked->paid_at ?: now();
                $payload['paid_by'] = $actor->id;
            }
            if (SettlementStatus::releasesItems($status)) {
                SettlementItem::query()
                    ->where('settlement_id', $locked->id)
                    ->whereNull('released_at')
                    ->update([
                        'released_at' => now(),
                        'active_payment_item_id' => null,
                    ]);
            }

            $locked->update($payload);
            $this->audit($locked, $actor, 'status_changed', $from, $status);

            return $locked->fresh();
        });

        $this->afterCommit($updated, $actor, $status === SettlementStatus::SETTLED);

        return $updated;
    }

    public function storeReceipt(User $actor, Settlement $settlement, UploadedFile $file): Settlement
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (! in_array($extension, $allowed, true)) {
            throw new SettlementException('فرمت رسید مجاز نیست.');
        }

        $stored = $this->storeReceiptFile($file, 'settlements/receipts/'.$settlement->id);

        return DB::transaction(function () use ($actor, $settlement, $file, $stored) {
            $locked = Settlement::query()->whereKey($settlement->id)->lockForUpdate()->firstOrFail();
            $previousId = $locked->current_receipt_id;
            if ($previousId) {
                SettlementReceipt::query()->whereKey($previousId)->update(['replaced_at' => now()]);
            }

            $receipt = SettlementReceipt::create([
                'settlement_id' => $locked->id,
                'disk' => $stored['disk'],
                'path' => $stored['path'],
                'original_name' => Str::limit(basename($file->getClientOriginalName()), 180, ''),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
                'uploaded_by' => $actor->id,
            ]);

            $locked->update(['current_receipt_id' => $receipt->id]);
            $this->audit($locked, $actor, $previousId ? 'receipt_replaced' : 'receipt_uploaded', null, null, [
                'receipt_id' => $receipt->id,
                'previous_receipt_id' => $previousId,
                'original_name' => $receipt->original_name,
            ]);

            return $locked->fresh(['currentReceipt']);
        });
    }

    public function deleteReceipt(User $actor, Settlement $settlement): Settlement
    {
        return DB::transaction(function () use ($actor, $settlement) {
            $locked = Settlement::query()->whereKey($settlement->id)->lockForUpdate()->firstOrFail();
            if (! $locked->current_receipt_id) {
                throw new SettlementException('رسیدی برای این تسویه ثبت نشده است.');
            }

            $receiptId = $locked->current_receipt_id;
            SettlementReceipt::query()->whereKey($receiptId)->update(['deleted_at' => now()]);
            $locked->update(['current_receipt_id' => null]);
            $this->audit($locked, $actor, 'receipt_deleted', null, null, ['receipt_id' => $receiptId]);

            return $locked->fresh();
        });
    }

    /**
     * @return array{updated: int, skipped: array<int, string>}
     */
    public function markPayments(User $actor, array $paymentIds, string $mode, array $input, ?UploadedFile $file, ?int $onlyTeacherId = null): array
    {
        if (! in_array($mode, ['settled', 'unsettled'], true)) {
            throw new SettlementException('وضعیت تسویه معتبر نیست.');
        }

        $paymentIds = array_values(array_unique(array_filter(array_map('intval', $paymentIds))));
        if ($paymentIds === []) {
            throw new SettlementException('هیچ پرداختی انتخاب نشده است.');
        }

        sort($paymentIds);
        $input['payouts'] = $this->resolvePayouts($input['payouts'] ?? []);
        $skipped = [];
        $created = collect();

        $touchedIds = DB::transaction(function () use ($actor, $paymentIds, $mode, $input, $onlyTeacherId, &$skipped, &$created) {
            $payments = Payment::query()
                ->with([
                    'items.payable',
                    'items.activeSettlementItem.settlement',
                    'coveredSettlement',
                ])
                ->whereIn('id', $paymentIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $scope = $actor->contentScope();
            $ready = collect();
            foreach ($paymentIds as $paymentId) {
                $payment = $payments->get($paymentId);
                if (! $payment || ! $scope->canPayment($payment, 'view')) {
                    throw new SettlementException('به یکی از پرداخت‌های انتخاب‌شده دسترسی ندارید.', 403);
                }
                if ($mode === 'settled' && ! TeacherShare::isPaid($payment)) {
                    $skipped[$payment->id] = 'فقط پرداخت موفق قابل تسویه است.';
                    continue;
                }
                $ready->push($payment);
            }

            if ($ready->isEmpty()) {
                $reason = $skipped !== [] ? implode(' ', array_unique($skipped)) : 'هیچ پرداخت قابل تغییری در انتخاب شما نیست.';
                throw new SettlementException($reason);
            }

            if ($mode === 'unsettled') {
                foreach ($ready as $payment) {
                    $items = $this->scopedItems($payment, $onlyTeacherId);
                    $this->releaseLockedItems($items, $actor);
                    if ($items->isEmpty() && ! $onlyTeacherId) {
                        $this->releaseCoveredPayment($payment, $actor);
                    }
                }
            } else {
                $pending = collect();
                foreach ($ready as $payment) {
                    $items = $this->scopedItems($payment, $onlyTeacherId);
                    if ($items->isEmpty() && ! $onlyTeacherId) {
                        $this->coverPayment($actor, $payment, $input, $created);
                        continue;
                    }
                    $active = $items->filter(fn (PaymentItem $item) => $item->activeSettlementItem && ! $this->itemSettled($item));
                    $this->releaseLockedItems($active, $actor);
                    foreach ($items as $item) {
                        $item->unsetRelation('activeSettlementItem');
                        if (! $this->itemSettled($item->fresh(['activeSettlementItem.settlement']))) {
                            $pending->push($item);
                        }
                    }
                }

                $groups = $pending->groupBy(function (PaymentItem $item) {
                    $teacherId = $this->teacherIdOf($item);

                    return $teacherId === null ? 'none' : (string) $teacherId;
                });
                $key = 'paymark:'.Str::uuid();
                foreach ($groups as $bucket => $group) {
                    $teacherId = $bucket === 'none' ? null : (int) $bucket;
                    $created->push($this->persistGroup(
                        $actor,
                        $teacherId,
                        $group,
                        SettlementStatus::SETTLED,
                        $input,
                        $key
                    ));
                }

                $this->touchExisting($ready, $onlyTeacherId, $input, $actor);
            }

            return $this->activeSettlementIds($ready->pluck('id')->all(), $onlyTeacherId);
        });

        if ($mode === 'settled' && $file && $touchedIds !== []) {
            $this->attachSharedReceipt($actor, $touchedIds, $file);
        }

        foreach ($created as $settlement) {
            $this->afterCommit($settlement, $actor, $settlement->teacher_id !== null);
        }

        return [
            'updated' => count($paymentIds) - count($skipped),
            'skipped' => array_values($skipped),
        ];
    }

    private function persistGroup(User $actor, ?int $teacherId, Collection $group, string $status, array $input, string $idempotencyKey): Settlement
    {
        $rates = app(SettlementCommission::class)->rates();
        $rows = $group->map(fn (PaymentItem $item) => TeacherShare::breakdown($item, $rates));
        $amount = (int) $rows->sum('teacher_amount');
        $grossAmount = (int) $rows->sum('gross_amount');
        $platformAmount = (int) $rows->sum('platform_amount');
        $paymentCount = $group->pluck('payment_id')->unique()->count();
        $paidAt = ! empty($input['paid_at']) ? now()->parse($input['paid_at']) : ($status === SettlementStatus::SETTLED ? now() : null);

        try {
            $settlement = Settlement::create(array_merge([
                'teacher_id' => $teacherId,
                'amount' => $amount,
                'gross_amount' => $grossAmount,
                'platform_amount' => $platformAmount,
                'site_percent' => $rates['site_percent'],
                'teacher_percent' => $rates['teacher_percent'],
                'payment_count' => $paymentCount,
                'status' => $status,
                'tracking_number' => $input['tracking_number'] ?? null,
                'iban' => $input['iban'] ?? null,
                'account_holder' => $input['account_holder'] ?? null,
                'description' => $input['description'] ?? null,
                'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey.':'.($teacherId ?? 'none') : null,
                'created_by' => $actor->id,
                'paid_by' => $status === SettlementStatus::SETTLED ? $actor->id : null,
                'paid_at' => $paidAt,
            ], $this->payoutColumns($input['payouts'][$teacherId] ?? null)));
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                throw new SettlementException('این درخواست تسویه قبلاً ثبت شده است.', 409);
            }
            throw $e;
        }

        foreach ($group as $item) {
            $split = TeacherShare::breakdown($item, $rates);
            try {
                SettlementItem::create([
                    'settlement_id' => $settlement->id,
                    'payment_id' => $item->payment_id,
                    'payment_item_id' => $item->id,
                    'teacher_id' => $teacherId,
                    'amount' => $split['teacher_amount'],
                    'gross_amount' => $split['gross_amount'],
                    'platform_amount' => $split['platform_amount'],
                    'site_percent' => $split['site_percent'],
                    'teacher_percent' => $split['teacher_percent'],
                    'charged_amount' => $split['charged_amount'],
                    'active_payment_item_id' => $item->id,
                ]);
            } catch (QueryException $e) {
                if ($this->isUniqueViolation($e)) {
                    throw new SettlementException('این تراکنش قبلاً در یک Settlement دیگر ثبت شده است.', 409);
                }
                throw $e;
            }
        }

        $this->audit($settlement, $actor, 'created', null, $status, [
            'payment_item_ids' => $group->pluck('id')->values()->all(),
            'payment_ids' => $group->pluck('payment_id')->unique()->values()->all(),
            'amount' => $amount,
            'gross_amount' => $grossAmount,
            'platform_amount' => $platformAmount,
            'site_percent' => $rates['site_percent'],
            'teacher_percent' => $rates['teacher_percent'],
        ]);

        return $settlement->fresh(['teacher', 'items', 'creator', 'payer', 'currentReceipt']);
    }

    private function loadCandidates(User $actor, array $paymentIds, array $paymentItemIds): Collection
    {
        if ($paymentIds === [] && $paymentItemIds === []) {
            throw new SettlementException('هیچ تراکنشی انتخاب نشده است.');
        }

        $query = PaymentItem::query()->with(['payment.user', 'payable', 'activeSettlementItem']);
        if ($paymentItemIds !== []) {
            $query->whereIn('id', $paymentItemIds);
        } else {
            $query->whereIn('payment_id', $paymentIds);
        }

        $items = $query->get();
        if ($items->isEmpty()) {
            throw new SettlementException('تراکنش انتخاب‌شده پیدا نشد.', 404);
        }

        $scope = $actor->contentScope();
        foreach ($items as $item) {
            if (! $item->payment || ! $scope->canPayment($item->payment, 'view')) {
                throw new SettlementException('به یکی از تراکنش‌های انتخاب‌شده دسترسی ندارید.', 403);
            }
        }

        return $items;
    }

    /**
     * @return array{0: Collection<int, Collection<int, PaymentItem>>, 1: array<int, array<string, mixed>>}
     */
    private function partition(Collection $items, bool $strictActive = false): array
    {
        $groups = collect();
        $blocked = [];

        if ($strictActive) {
            $busy = SettlementItem::query()
                ->whereIn('payment_item_id', $items->pluck('id'))
                ->whereNull('released_at')
                ->pluck('payment_item_id')
                ->all();
        } else {
            $busy = [];
        }

        foreach ($items as $item) {
            $reason = $this->blockReason($item, $strictActive, $busy);
            if ($reason !== null) {
                $blocked[] = [
                    'payment_id' => $item->payment_id,
                    'payment_item_id' => $item->id,
                    'message' => $reason,
                ];
                continue;
            }

            $teacherId = (int) $item->payable->teacher_id;
            $bucket = $groups->get($teacherId, collect());
            $bucket->push($item);
            $groups->put($teacherId, $bucket);
        }

        return [$groups, $blocked];
    }

    private function blockReason(PaymentItem $item, bool $strictActive, array $busy): ?string
    {
        $payment = $item->payment;
        if (! $payment || ! TeacherShare::isPaid($payment)) {
            return 'فقط پرداخت‌های موفق قابل تسویه هستند.';
        }
        if (! TeacherShare::isCourse($item) || ! $item->payable instanceof Course || ! $item->payable->teacher_id) {
            return 'این مورد دوره مدرس ندارد و قابل تسویه نیست.';
        }
        if (TeacherShare::amount($item) <= 0) {
            return 'سهم مدرس این تراکنش صفر است.';
        }

        $active = $strictActive
            ? in_array($item->id, $busy, true)
            : $item->activeSettlementItem !== null;
        if ($active) {
            return 'این تراکنش قبلاً در یک Settlement دیگر ثبت شده است.';
        }

        return null;
    }

    private function groupPayload(Collection $group): array
    {
        /** @var Course $course */
        $course = $group->first()->payable;
        $teacher = $course->teacher;

        $rates = app(SettlementCommission::class)->rates();
        $rows = $group->map(fn (PaymentItem $item) => TeacherShare::breakdown($item, $rates));
        $share = [
            'gross_amount' => (int) $rows->sum('gross_amount'),
            'platform_amount' => (int) $rows->sum('platform_amount'),
            'teacher_amount' => (int) $rows->sum('teacher_amount'),
            'site_percent' => $rates['site_percent'],
            'teacher_percent' => $rates['teacher_percent'],
        ];

        return [
            'teacher' => [
                'id' => $teacher->id,
                'name' => trim($teacher->first_name.' '.$teacher->last_name),
            ],
            'payment_count' => $group->pluck('payment_id')->unique()->count(),
            'item_count' => $group->count(),
            'sales_amount' => (int) $group->sum(fn (PaymentItem $item) => TeacherShare::chargedAmount($item)),
            'teacher_share' => $share['teacher_amount'],
            'share' => $share,
            'payment_ids' => $group->pluck('payment_id')->unique()->values()->all(),
            'payment_item_ids' => $group->pluck('id')->values()->all(),
        ];
    }

    public function sharePreview(User $actor, array $paymentIds, ?int $onlyTeacherId = null): array
    {
        $commission = app(SettlementCommission::class);
        $rates = $commission->rates();
        $payments = Payment::query()
            ->with(['items.payable', 'items.activeSettlementItem.settlement', 'coveredSettlement'])
            ->whereIn('id', array_values(array_unique(array_filter(array_map('intval', $paymentIds)))))
            ->get();
        $scope = $actor->contentScope();
        $gross = 0;
        $platform = 0;
        $teacher = 0;
        $count = 0;
        $sitePercent = $rates['site_percent'];
        $teacherPercent = $rates['teacher_percent'];
        $fromSnapshot = false;

        foreach ($payments as $payment) {
            if (! $scope->canPayment($payment, 'view')) {
                continue;
            }
            $items = $this->scopedItems($payment, $onlyTeacherId);
            if ($items->isEmpty() && ! $onlyTeacherId) {
                $covered = $payment->coveredSettlement;
                if ($covered && $covered->status === SettlementStatus::SETTLED) {
                    $gross += (int) ($covered->gross_amount ?: $covered->amount);
                    $platform += (int) ($covered->platform_amount ?? 0);
                    $teacher += (int) $covered->amount;
                    $sitePercent = (float) ($covered->site_percent ?? $sitePercent);
                    $teacherPercent = (float) ($covered->teacher_percent ?? $teacherPercent);
                    $fromSnapshot = true;
                    $count += 1;
                    continue;
                }
                $split = $commission->split((int) ($payment->base_amount ?: $payment->amount), $rates);
                $gross += $split['gross_amount'];
                $platform += $split['platform_amount'];
                $teacher += $split['teacher_amount'];
                $count += 1;
                continue;
            }
            foreach ($items as $item) {
                if ($this->itemSettled($item)) {
                    $settledItem = $item->activeSettlementItem;
                    if ($settledItem) {
                        $gross += (int) ($settledItem->gross_amount ?: $settledItem->amount);
                        $platform += (int) ($settledItem->platform_amount ?? 0);
                        $teacher += (int) $settledItem->amount;
                        $sitePercent = (float) ($settledItem->site_percent ?? $sitePercent);
                        $teacherPercent = (float) ($settledItem->teacher_percent ?? $teacherPercent);
                        $fromSnapshot = true;
                        $count += 1;
                    }
                    continue;
                }
                $split = TeacherShare::breakdown($item, $rates);
                $gross += $split['gross_amount'];
                $platform += $split['platform_amount'];
                $teacher += $split['teacher_amount'];
                $count += 1;
            }
        }

        $share = [
            'gross_amount' => $gross,
            'platform_amount' => $platform,
            'teacher_amount' => $teacher,
            'site_percent' => $fromSnapshot ? $sitePercent : $rates['site_percent'],
            'teacher_percent' => $fromSnapshot ? $teacherPercent : $rates['teacher_percent'],
            'item_count' => $count,
            'from_snapshot' => $fromSnapshot,
        ];

        return array_merge($share, [
            'explain' => $commission->explain($share),
        ]);
    }

    private function findIdempotent(User $actor, string $key): Collection
    {
        return Settlement::query()
            ->with(['teacher', 'items', 'creator', 'payer', 'currentReceipt'])
            ->where('created_by', $actor->id)
            ->where(function ($query) use ($key) {
                $query->where('idempotency_key', $key)
                    ->orWhere('idempotency_key', 'like', $key.':%');
            })
            ->get();
    }

    public function payoutRecipients(User $actor, array $paymentIds, ?int $onlyTeacherId): array
    {
        $paymentIds = array_values(array_unique(array_filter(array_map('intval', $paymentIds))));
        $payments = Payment::query()->with(['items.payable', 'user'])->whereIn('id', $paymentIds)->get();
        $scope = $actor->contentScope();
        $userIds = [];

        foreach ($payments as $payment) {
            if (! $scope->canPayment($payment, 'view')) {
                throw new SettlementException('به یکی از پرداخت‌های انتخاب‌شده دسترسی ندارید.', 403);
            }
            $items = $this->scopedItems($payment, $onlyTeacherId);
            $found = false;
            foreach ($items as $item) {
                $teacherId = $this->teacherIdOf($item);
                if ($teacherId) {
                    $userIds[] = $teacherId;
                    $found = true;
                }
            }
            if (! $found && ! $onlyTeacherId && $payment->user_id) {
                $userIds[] = (int) $payment->user_id;
            }
        }

        $userIds = array_values(array_unique($userIds));
        $users = User::query()->whereIn('id', $userIds)->get()->keyBy('id');
        $accounts = UserBankAccount::query()
            ->whereIn('user_id', $userIds)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get()
            ->groupBy('user_id');
        $inspector = app(IranianBankInspector::class);

        return collect($userIds)->map(function (int $id) use ($users, $accounts, $inspector) {
            $user = $users->get($id);

            return [
                'user_id' => $id,
                'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->email ?? 'کاربر'),
                'accounts' => ($accounts->get($id) ?? collect())->map(function (UserBankAccount $account) use ($inspector) {
                    return [
                        'id' => $account->id,
                        'kind' => $account->kind,
                        'formatted' => $inspector->format($account->kind, $account->number),
                        'owner_name' => $account->owner_name,
                        'is_default' => (bool) $account->is_default,
                        'bank' => IranianBanks::find($account->bank_code),
                    ];
                })->values(),
            ];
        })->values()->all();
    }

    /**
     * @param  array<int, array{user_id?: int, bank_account_id?: int}>  $rows
     * @return array<int, UserBankAccount>
     */
    private function resolvePayouts(array $rows): array
    {
        $map = [];
        foreach ($rows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $accountId = (int) ($row['bank_account_id'] ?? 0);
            if ($userId === 0 || $accountId === 0) {
                continue;
            }
            $account = UserBankAccount::query()->whereKey($accountId)->where('user_id', $userId)->first();
            if (! $account) {
                throw new SettlementException('حساب بانکی انتخاب‌شده متعلق به این کاربر نیست.');
            }
            $map[$userId] = $account;
        }

        return $map;
    }

    private function payoutColumns(?UserBankAccount $account): array
    {
        if (! $account) {
            return [];
        }

        return [
            'iban' => $account->kind === 'sheba' ? $account->number : null,
            'account_holder' => $account->owner_name,
            'payout_kind' => $account->kind,
            'payout_number' => $account->number,
            'payout_bank_code' => $account->bank_code,
            'user_bank_account_id' => $account->id,
        ];
    }

    private function scopedItems(Payment $payment, ?int $onlyTeacherId): Collection
    {
        $items = $payment->items;
        if (! $onlyTeacherId) {
            return $items->values();
        }

        return $items->filter(fn (PaymentItem $item) => $this->teacherIdOf($item) === $onlyTeacherId)->values();
    }

    private function teacherIdOf(PaymentItem $item): ?int
    {
        if (! TeacherShare::isCourse($item) || ! $item->payable instanceof Course || ! $item->payable->teacher_id) {
            return null;
        }

        return (int) $item->payable->teacher_id;
    }

    private function itemSettled(PaymentItem $item): bool
    {
        return $item->activeSettlementItem?->settlement?->status === SettlementStatus::SETTLED;
    }

    private function releaseLockedItems(Collection $items, User $actor): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $links = SettlementItem::query()
            ->whereIn('payment_item_id', $items->pluck('id'))
            ->whereNull('released_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $settlementIds = [];
        foreach ($links as $link) {
            $settlementIds[] = $link->settlement_id;
            $link->update([
                'released_at' => now(),
                'active_payment_item_id' => null,
            ]);
        }

        foreach (array_unique($settlementIds) as $id) {
            $this->rebalanceSettlement((int) $id, $actor);
        }
    }

    private function rebalanceSettlement(int $id, User $actor): void
    {
        $settlement = Settlement::query()->whereKey($id)->lockForUpdate()->first();
        if (! $settlement) {
            return;
        }

        $active = SettlementItem::query()->where('settlement_id', $id)->whereNull('released_at')->get();
        if ($active->isEmpty() && ! $settlement->active_covered_payment_id) {
            $from = $settlement->status;
            if (! in_array($from, SettlementStatus::ACTIVE, true)) {
                return;
            }
            $to = $from === SettlementStatus::SETTLED ? SettlementStatus::REVERSED : SettlementStatus::CANCELLED;
            $settlement->update(['status' => $to, 'amount' => 0, 'payment_count' => 0]);
            $this->audit($settlement, $actor, 'status_changed', $from, $to, ['reason' => 'payment_unsettled']);

            return;
        }

        $settlement->update([
            'amount' => (int) $active->sum('amount'),
            'payment_count' => $active->pluck('payment_id')->unique()->count(),
        ]);
    }

    private function coverPayment(User $actor, Payment $payment, array $input, Collection $created): void
    {
        $existing = Settlement::query()->where('active_covered_payment_id', $payment->id)->lockForUpdate()->first();
        if ($existing && $existing->status === SettlementStatus::SETTLED) {
            return;
        }
        if ($existing) {
            $existing->update([
                'active_covered_payment_id' => null,
                'status' => SettlementStatus::CANCELLED,
            ]);
        }

        $split = app(SettlementCommission::class)->split((int) ($payment->base_amount ?: $payment->amount));
        $settlement = Settlement::create(array_merge([
            'teacher_id' => null,
            'amount' => $split['teacher_amount'],
            'gross_amount' => $split['gross_amount'],
            'platform_amount' => $split['platform_amount'],
            'site_percent' => $split['site_percent'],
            'teacher_percent' => $split['teacher_percent'],
            'payment_count' => 1,
            'status' => SettlementStatus::SETTLED,
            'tracking_number' => $input['tracking_number'] ?? null,
            'description' => $input['description'] ?? null,
            'idempotency_key' => 'paycover:'.Str::uuid(),
            'created_by' => $actor->id,
            'paid_by' => $actor->id,
            'paid_at' => now(),
            'active_covered_payment_id' => $payment->id,
        ], $this->payoutColumns($input['payouts'][(int) $payment->user_id] ?? null)));
        $this->audit($settlement, $actor, 'created', null, SettlementStatus::SETTLED, [
            'payment_ids' => [$payment->id],
            'covered_payment' => true,
            'gross_amount' => $split['gross_amount'],
            'platform_amount' => $split['platform_amount'],
            'site_percent' => $split['site_percent'],
            'teacher_percent' => $split['teacher_percent'],
        ]);
        $created->push($settlement);
    }

    private function releaseCoveredPayment(Payment $payment, User $actor): void
    {
        $existing = Settlement::query()->where('active_covered_payment_id', $payment->id)->lockForUpdate()->first();
        if (! $existing || ! in_array($existing->status, SettlementStatus::ACTIVE, true)) {
            return;
        }

        $from = $existing->status;
        $to = $from === SettlementStatus::SETTLED ? SettlementStatus::REVERSED : SettlementStatus::CANCELLED;
        $existing->update([
            'active_covered_payment_id' => null,
            'status' => $to,
        ]);
        $this->audit($existing, $actor, 'status_changed', $from, $to, ['reason' => 'payment_unsettled']);
    }

    private function touchExisting(Collection $payments, ?int $onlyTeacherId, array $input, User $actor): void
    {
        $shared = [];
        foreach (['tracking_number', 'description'] as $field) {
            if (! empty($input[$field])) {
                $shared[$field] = $input[$field];
            }
        }
        $payouts = $input['payouts'] ?? [];
        if ($shared === [] && $payouts === []) {
            return;
        }

        $ids = $this->activeSettlementIds($payments->pluck('id')->all(), $onlyTeacherId);
        if ($ids === []) {
            return;
        }

        foreach ($ids as $id) {
            $settlement = Settlement::query()->find($id);
            if (! $settlement) {
                continue;
            }
            $payload = $shared;
            $ownerId = $settlement->teacher_id;
            if (! $ownerId && $settlement->active_covered_payment_id) {
                $ownerId = Payment::query()->whereKey($settlement->active_covered_payment_id)->value('user_id');
            }
            if ($ownerId && isset($payouts[(int) $ownerId])) {
                $payload = array_merge($payload, $this->payoutColumns($payouts[(int) $ownerId]));
            }
            if ($payload === []) {
                continue;
            }
            $settlement->update($payload);
            $this->audit($settlement, $actor, 'updated', null, null, $payload);
        }
    }

    private function activeSettlementIds(array $paymentIds, ?int $onlyTeacherId): array
    {
        $items = SettlementItem::query()
            ->whereIn('payment_id', $paymentIds)
            ->whereNull('released_at');
        if ($onlyTeacherId) {
            $items->where('teacher_id', $onlyTeacherId);
        }

        $ids = $items->pluck('settlement_id');
        if (! $onlyTeacherId) {
            $ids = $ids->merge(
                Settlement::query()->whereIn('active_covered_payment_id', $paymentIds)->pluck('id')
            );
        }

        return $ids->unique()->filter()->values()->all();
    }

    private function attachSharedReceipt(User $actor, array $settlementIds, UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (! in_array($extension, $allowed, true)) {
            throw new SettlementException('فرمت رسید مجاز نیست.');
        }

        $stored = $this->storeReceiptFile($file, 'settlements/receipts/shared');

        DB::transaction(function () use ($actor, $settlementIds, $file, $stored) {
            $settlements = Settlement::query()->whereIn('id', $settlementIds)->orderBy('id')->lockForUpdate()->get();
            foreach ($settlements as $settlement) {
                if ($settlement->current_receipt_id) {
                    SettlementReceipt::query()->whereKey($settlement->current_receipt_id)->update(['replaced_at' => now()]);
                }
                $receipt = SettlementReceipt::create([
                    'settlement_id' => $settlement->id,
                    'disk' => $stored['disk'],
                    'path' => $stored['path'],
                    'original_name' => Str::limit(basename($file->getClientOriginalName()), 180, ''),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize() ?: 0,
                    'uploaded_by' => $actor->id,
                ]);
                $settlement->update(['current_receipt_id' => $receipt->id]);
                $this->audit($settlement, $actor, 'receipt_uploaded', null, null, [
                    'receipt_id' => $receipt->id,
                    'shared' => true,
                ]);
            }
        });
    }

    /**
     * @return array{disk: string, path: string}
     */
    private function storeReceiptFile(UploadedFile $file, string $directory): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
            throw new SettlementException('فرمت رسید مجاز نیست.');
        }

        $filename = (string) Str::uuid().'.'.$extension;
        foreach (['media', 'local'] as $disk) {
            try {
                $path = $file->storeAs($directory, $filename, $disk);
                if ($path) {
                    return ['disk' => $disk, 'path' => $path];
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        throw new SettlementException('ذخیره رسید انجام نشد.');
    }

    private function audit(Settlement $settlement, ?User $actor, string $action, ?string $from, ?string $to, array $meta = []): void
    {
        SettlementAudit::create([
            'settlement_id' => $settlement->id,
            'user_id' => $actor?->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }

    private function afterCommit(Settlement $settlement, User $actor, bool $notifyTeacher): void
    {
        $settlement->loadMissing('teacher');
        DB::afterCommit(function () use ($settlement, $actor, $notifyTeacher) {
            event(new SettlementChanged($settlement->id, $settlement->teacher_id, $settlement->status, $actor->id));
            if ($notifyTeacher && $settlement->teacher) {
                try {
                    $settlement->teacher->notify(new SettlementPaidNotification($settlement));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? '';

        return in_array($sqlState, ['23505', '23000'], true);
    }
};
