<?php

namespace App\Services\Settlement;

use Carbon\Carbon;
use Illuminate\Http\Request;

class SettlementPeriod
{
    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: string}
     */
    public static function fromRequest(Request $request, bool $defaultToday = false): array
    {
        $preset = (string) $request->input('date_preset', $defaultToday ? 'today' : '');

        if ($preset === '' && $request->filled('paid_from') && $request->filled('paid_to')) {
            $preset = 'custom';
        }

        return self::resolve(
            $preset !== '' ? $preset : null,
            $request->input('paid_from', $request->input('date_from')),
            $request->input('paid_to', $request->input('date_to'))
        );
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: string}
     */
    public static function resolve(?string $preset, ?string $from = null, ?string $to = null): array
    {
        $now = now();

        return match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'today'],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'yesterday'],
            'this_week' => [
                $now->copy()->startOfWeek(Carbon::SATURDAY)->startOfDay(),
                $now->copy()->endOfWeek(Carbon::FRIDAY)->endOfDay(),
                'this_week',
            ],
            'last_week' => [
                $now->copy()->subWeek()->startOfWeek(Carbon::SATURDAY)->startOfDay(),
                $now->copy()->subWeek()->endOfWeek(Carbon::FRIDAY)->endOfDay(),
                'last_week',
            ],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'this_month'],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow()->endOfMonth(),
                'last_month',
            ],
            'custom' => [
                Carbon::parse($from ?: $now)->startOfDay(),
                Carbon::parse($to ?: $from ?: $now)->endOfDay(),
                'custom',
            ],
            default => [null, null, 'all'],
        };
    }
};
