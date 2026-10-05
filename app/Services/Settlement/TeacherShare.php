<?php

namespace App\Services\Settlement;

use App\Models\Course;
use App\Models\Payment;
use App\Models\PaymentItem;

/**
 * Teacher revenue already stored on payment items.
 *
 * Sales reports attribute course revenue with:
 * GREATEST(0, COALESCE(final_price, price - discount_amount))
 * Gateway fees are charged on top of that amount and are not part of it.
 * The project does not store a separate teacher/platform commission rate.
 */
class TeacherShare
{
    public const SQL = 'GREATEST(0, COALESCE(payment_items.final_price, payment_items.price - COALESCE(payment_items.discount_amount, 0)))';

    public static function amount(PaymentItem $item): int
    {
        $final = $item->final_price;
        if ($final === null) {
            $final = (int) $item->price - (int) ($item->discount_amount ?? 0);
        }

        return max(0, (int) $final);
    }

    public static function chargedAmount(PaymentItem $item): int
    {
        if ($item->charged_price !== null) {
            return max(0, (int) $item->charged_price);
        }

        return self::amount($item);
    }

    public static function isCourse(PaymentItem $item): bool
    {
        return $item->payable_type === Course::class;
    }

    public static function paymentStatus(Payment $payment): string
    {
        if ($payment->status && $payment->paid_at) {
            return 'paid';
        }

        $description = (string) ($payment->description ?? '');
        if (! $payment->status && ! $payment->paid_at && str_contains($description, 'لغو')) {
            return 'cancelled';
        }

        if ($payment->expired_at && $payment->expired_at->isPast()) {
            return 'expired';
        }

        if ($payment->relationLoaded('attempts')) {
            $latest = $payment->attempts->first();
            if ($latest && in_array((string) $latest->status, ['failed', 'fail', 'error'], true)) {
                return 'failed';
            }
        }

        return 'pending';
    }

    public static function isPaid(Payment $payment): bool
    {
        return (bool) $payment->status && $payment->paid_at !== null;
    }
}
