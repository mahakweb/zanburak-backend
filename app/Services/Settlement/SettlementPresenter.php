<?php

namespace App\Services\Settlement;

use App\Models\Course;
use App\Models\Settlement;
use App\Models\SettlementAudit;
use App\Models\User;
use App\Support\SettlementStatus;

class SettlementPresenter
{
    public function __construct(private User $viewer)
    {
    }

    public function listItem(Settlement $settlement): array
    {
        return $this->base($settlement);
    }

    public function detail(Settlement $settlement): array
    {
        $payload = $this->base($settlement);
        $payload['items'] = $settlement->items->map(function ($item) {
            $course = $item->paymentItem?->payable instanceof Course ? $item->paymentItem->payable : null;
            $payment = $item->payment;

            return [
                'id' => $item->id,
                'payment_id' => $item->payment_id,
                'payment_uuid' => $payment?->uuid,
                'payment_item_id' => $item->payment_item_id,
                'amount' => (int) $item->amount,
                'charged_amount' => (int) $item->charged_amount,
                'released_at' => optional($item->released_at)->toIso8601String(),
                'course' => $course ? [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                ] : null,
                'buyer' => $payment?->user ? [
                    'id' => $payment->user->id,
                    'name' => trim($payment->user->first_name.' '.$payment->user->last_name),
                ] : null,
                'paid_at' => optional($payment?->paid_at)->toIso8601String(),
            ];
        })->values();
        $payload['audits'] = $settlement->audits->map(fn (SettlementAudit $audit) => $this->audit($audit))->values();
        $payload['can'] = $this->abilities($settlement);

        return $payload;
    }

    public function audit(SettlementAudit $audit): array
    {
        return [
            'id' => $audit->id,
            'action' => $audit->action,
            'from_status' => $audit->from_status,
            'to_status' => $audit->to_status,
            'from_label' => $audit->from_status ? SettlementStatus::label($audit->from_status) : null,
            'to_label' => $audit->to_status ? SettlementStatus::label($audit->to_status) : null,
            'meta' => $audit->meta,
            'created_at' => optional($audit->created_at)->toIso8601String(),
            'user' => $audit->user ? [
                'id' => $audit->user->id,
                'name' => trim($audit->user->first_name.' '.$audit->user->last_name),
            ] : null,
        ];
    }

    private function base(Settlement $settlement): array
    {
        return [
            'id' => $settlement->id,
            'uuid' => $settlement->uuid,
            'amount' => (int) $settlement->amount,
            'payment_count' => (int) $settlement->payment_count,
            'status' => $settlement->status,
            'label' => SettlementStatus::label($settlement->status),
            'tracking_number' => $settlement->tracking_number,
            'iban' => $this->canSeeBank($settlement) ? $settlement->iban : null,
            'account_holder' => $this->canSeeBank($settlement) ? $settlement->account_holder : null,
            'description' => $settlement->description,
            'paid_at' => optional($settlement->paid_at)->toIso8601String(),
            'created_at' => optional($settlement->created_at)->toIso8601String(),
            'has_receipt' => (bool) $settlement->current_receipt_id,
            'receipt' => $settlement->currentReceipt && ! $settlement->currentReceipt->deleted_at ? [
                'id' => $settlement->currentReceipt->id,
                'original_name' => $settlement->currentReceipt->original_name,
                'mime' => $settlement->currentReceipt->mime,
                'size' => (int) $settlement->currentReceipt->size,
            ] : null,
            'teacher' => $settlement->teacher ? [
                'id' => $settlement->teacher->id,
                'name' => trim($settlement->teacher->first_name.' '.$settlement->teacher->last_name),
                'username' => $settlement->teacher->username,
            ] : null,
            'created_by' => $this->person($settlement->creator),
            'paid_by' => $this->person($settlement->payer),
            'can' => $this->abilities($settlement),
        ];
    }

    private function person(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => trim($user->first_name.' '.$user->last_name),
        ];
    }

    private function canSeeBank(Settlement $settlement): bool
    {
        return $this->viewer->isSuperUser()
            || $this->viewer->contentScope()->canAction('settlements', 'update', $settlement)
            || $this->viewer->contentScope()->canAction('settlements', 'create');
    }

    private function abilities(Settlement $settlement): array
    {
        $scope = $this->viewer->contentScope();

        return [
            'update' => $scope->canAction('settlements', 'update', $settlement),
            'change_status' => $scope->canAction('settlements', 'change_status', $settlement),
            'upload_receipt' => $scope->canAction('settlements', 'upload_receipt', $settlement),
            'view_receipt' => $scope->canAction('settlements', 'view_receipt', $settlement),
            'delete_receipt' => $scope->canAction('settlements', 'delete_receipt', $settlement),
        ];
    }
};
