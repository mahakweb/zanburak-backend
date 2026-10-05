<?php

namespace App\Services\Settlement;

use App\Models\Course;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\User;
use App\Support\SettlementStatus;

class PaymentSettlementPresenter
{
    public function __construct(private ?int $onlyTeacherId = null)
    {
    }

    public static function forUser(?User $user): self
    {
        if (! $user || $user->isSuperUser()) {
            return new self(null);
        }

        $scope = $user->contentScope();
        $seesAll = $scope->viewScope('payments') === 'any'
            || $scope->viewScope('settlements') === 'any';

        if ($seesAll) {
            return new self(null);
        }

        if ($scope->viewScope('payments') === 'own' || $scope->viewScope('settlements') === 'own') {
            return new self((int) $user->id);
        }

        return new self(-1);
    }

    public function summarize(Payment $payment): array
    {
        $lines = [];
        foreach ($payment->items as $item) {
            $line = $this->line($payment, $item);
            if ($line !== null) {
                $lines[] = $line;
            }
        }

        $eligible = array_values(array_filter($lines, fn ($line) => $line['eligible']));
        $share = array_sum(array_column($eligible, 'teacher_share'));
        $charged = array_sum(array_column($eligible, 'charged_amount'));
        $status = $this->rollup($payment, $eligible);

        $settlements = [];
        foreach ($eligible as $line) {
            if (! empty($line['settlement']['uuid'])) {
                $settlements[$line['settlement']['uuid']] = $line['settlement'];
            }
        }
        $settlementList = array_values($settlements);

        return [
            'status' => $status,
            'label' => SettlementStatus::label($status),
            'teacher_share' => (int) $share,
            'charged_amount' => (int) $charged,
            'can_settle' => TeacherShare::isPaid($payment) && collect($eligible)->contains(fn ($line) => $line['can_settle']),
            'payment_status' => TeacherShare::paymentStatus($payment),
            'teachers' => $this->uniquePeople($eligible, 'teacher'),
            'courses' => array_map(fn ($line) => [
                'id' => $line['course_id'],
                'title' => $line['course_title'],
            ], $eligible),
            'lines' => $lines,
            'settlement' => count($settlementList) === 1 ? $settlementList[0] : null,
            'settlements' => $settlementList,
        ];
    }

    private function line(Payment $payment, PaymentItem $item): ?array
    {
        if (! TeacherShare::isCourse($item)) {
            return null;
        }

        $course = $item->payable instanceof Course ? $item->payable : null;
        $teacher = $course?->teacher;
        if ($this->onlyTeacherId === -1) {
            return null;
        }
        if ($this->onlyTeacherId && (int) ($teacher?->id ?? 0) !== $this->onlyTeacherId) {
            return null;
        }

        $share = TeacherShare::amount($item);
        $active = $item->relationLoaded('activeSettlementItem') ? $item->activeSettlementItem : $item->activeSettlementItem()->first();
        $settlement = $active?->settlement;
        $eligible = $teacher && $share > 0;
        $lineStatus = SettlementStatus::NOT_APPLICABLE;
        if ($eligible && TeacherShare::isPaid($payment)) {
            $lineStatus = $settlement?->status ?? SettlementStatus::PENDING;
        }

        return [
            'payment_item_id' => $item->id,
            'course_id' => $course?->id,
            'course_title' => $course?->title,
            'teacher' => $teacher ? [
                'id' => $teacher->id,
                'name' => trim($teacher->first_name.' '.$teacher->last_name),
            ] : null,
            'teacher_share' => $share,
            'charged_amount' => TeacherShare::chargedAmount($item),
            'eligible' => $eligible && TeacherShare::isPaid($payment),
            'can_settle' => $eligible && TeacherShare::isPaid($payment) && $active === null,
            'status' => $lineStatus,
            'label' => SettlementStatus::label($lineStatus),
            'settlement' => $settlement ? [
                'id' => $settlement->id,
                'uuid' => $settlement->uuid,
                'status' => $settlement->status,
                'label' => SettlementStatus::label($settlement->status),
                'paid_at' => optional($settlement->paid_at)->toIso8601String(),
                'tracking_number' => $settlement->tracking_number,
                'has_receipt' => (bool) $settlement->current_receipt_id,
            ] : null,
        ];
    }

    private function rollup(Payment $payment, array $eligible): string
    {
        if (! TeacherShare::isPaid($payment) || $eligible === []) {
            return SettlementStatus::NOT_APPLICABLE;
        }

        $statuses = array_column($eligible, 'status');
        if (in_array(SettlementStatus::PENDING, $statuses, true)) {
            return SettlementStatus::PENDING;
        }
        if (in_array(SettlementStatus::PROCESSING, $statuses, true)) {
            return SettlementStatus::PROCESSING;
        }
        if (count(array_unique($statuses)) === 1 && $statuses[0] === SettlementStatus::SETTLED) {
            return SettlementStatus::SETTLED;
        }
        if (in_array(SettlementStatus::REVERSED, $statuses, true)) {
            return SettlementStatus::REVERSED;
        }
        if (in_array(SettlementStatus::CANCELLED, $statuses, true)) {
            return SettlementStatus::CANCELLED;
        }

        return SettlementStatus::PENDING;
    }

    private function uniquePeople(array $lines, string $key): array
    {
        $people = [];
        foreach ($lines as $line) {
            if (! empty($line[$key]['id'])) {
                $people[$line[$key]['id']] = $line[$key];
            }
        }

        return array_values($people);
    }
};
