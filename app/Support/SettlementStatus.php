<?php

namespace App\Support;

class SettlementStatus
{
    public const NOT_APPLICABLE = 'not_applicable';

    public const PENDING = 'pending';

    public const PROCESSING = 'processing';

    public const SETTLED = 'settled';

    public const CANCELLED = 'cancelled';

    public const REVERSED = 'reversed';

    public const ACTIVE = [
        self::PENDING,
        self::PROCESSING,
        self::SETTLED,
    ];

    public const ALL = [
        self::PENDING,
        self::PROCESSING,
        self::SETTLED,
        self::CANCELLED,
        self::REVERSED,
    ];

    public static function transitions(): array
    {
        return [
            self::PENDING => [self::PROCESSING, self::SETTLED, self::CANCELLED],
            self::PROCESSING => [self::PENDING, self::SETTLED, self::CANCELLED],
            self::SETTLED => [self::REVERSED],
            self::CANCELLED => [],
            self::REVERSED => [],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::transitions()[$from] ?? [], true);
    }

    public static function releasesItems(string $status): bool
    {
        return in_array($status, [self::CANCELLED, self::REVERSED], true);
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::NOT_APPLICABLE => 'غیرقابل تسویه',
            self::PENDING => 'در انتظار تسویه',
            self::PROCESSING => 'در حال پردازش',
            self::SETTLED => 'تسویه شده',
            self::CANCELLED => 'لغو شده',
            self::REVERSED => 'برگشت خورده',
            default => $status,
        };
    }
}
