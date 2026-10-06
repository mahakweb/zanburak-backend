<?php

namespace App\Services\Settlement;

use App\Models\Course;
use App\Models\Payment;
use App\Models\PaymentItem;

/**
 * Teacher revenue based on payment item final price after discount.
 *
 * Gross = GREATEST(0, COALESCE(final_price, price - discount_amount))
 * Gateway fees are charged on top and are not part of this base.
 * Platform/teacher split comes from SettlementCommission (site settings).
 */
class TeacherShare
{
    /**
     * Gross SQL expression (before platform cut).
     */
    public const GROSS_SQL = 'GREATEST(0, COALESCE(payment_items.final_price, payment_items.price - COALESCE(payment_items.discount_amount, 0)))';

    /**
     * @deprecated Use sql() — kept for callers that still reference the constant name.
     */
    public const SQL = self::GROSS_SQL;

    public static function grossAmount(PaymentItem $item): int
    {
        $final = $item->final_price;
        if ($final === null) {
            $final = (int) $item->price - (int) ($item->discount_amount ?? 0);
        }

        return max(0, (int) $final);
    }

    /**
     * Teacher/user net share after platform cut.
     */
    public static function amount(PaymentItem $item): int
    {
        return self::breakdown($item)['teacher_amount'];
    }

    public static function platformAmount(PaymentItem $item): int
    {
        return self::breakdown($item)['platform_amount'];
    }

    /**
     * @return array{
     *   gross_amount: int,
     *   site_percent: float,
     *   teacher_percent: float,
     *   platform_amount: int,
     *   teacher_amount: int,
     *   charged_amount: int
     * }
     */
    public static function breakdown(PaymentItem $item, ?array $rates = null): array
    {
        $commission = app(SettlementCommission::class);
        $split = $commission->split(self::grossAmount($item), $rates);

        return array_merge($split, [
            'charged_amount' => self::chargedAmount($item),
        ]);
    }

    /**
     * SQL for teacher net share using current settings.
     */
    public static function sql(?array $rates = null): string
    {
        $rates ??= app(SettlementCommission::class)->rates();
        $percent = (float) $rates['teacher_percent'];
        $gross = self::GROSS_SQL;

        if ($percent >= 100) {
            return $gross;
        }
        if ($percent <= 0) {
            return '0';
        }

        $safe = number_format($percent, 2, '.', '');

        // BIGINT works on both PostgreSQL and MySQL; SIGNED is MySQL-only.
        return "CAST(ROUND(({$gross}) * {$safe} / 100.0) AS BIGINT)";
    }

    public static function chargedAmount(PaymentItem $item): int
    {
        if ($item->charged_price !== null) {
            return max(0, (int) $item->charged_price);
        }

        return self::grossAmount($item);
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
