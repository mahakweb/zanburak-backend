<?php

namespace App\Services\Settlement;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StatusTrend
{
    /**
     * @return array{0: Carbon, 1: Carbon, 2: string, 3: string}
     */
    public function period(Request $request): array
    {
        $preset = (string) $request->input('preset', 'month');
        $grain = (string) $request->input('grain', 'day');
        if (! in_array($grain, ['day', 'month', 'year'], true)) {
            $grain = 'day';
        }

        $now = now();
        if ($preset === 'today') {
            $from = $now->copy()->startOfDay();
            $to = $now->copy()->endOfDay();
        } elseif ($preset === 'week') {
            $from = $now->copy()->startOfWeek(Carbon::SATURDAY)->startOfDay();
            $to = $from->copy()->addDays(6)->endOfDay();
        } elseif ($preset === 'year') {
            $from = $now->copy()->startOfYear();
            $to = $now->copy()->endOfYear();
        } elseif ($preset === 'custom') {
            $from = Carbon::parse($request->input('date_from', $now))->startOfDay();
            $to = Carbon::parse($request->input('date_to', $now))->endOfDay();
        } else {
            $preset = 'month';
            $from = $now->copy()->startOfMonth();
            $to = $now->copy()->endOfMonth();
        }

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if ($grain === 'day' && $from->diffInDays($to) > 120) {
            $grain = 'month';
        }

        return [$from, $to, $grain, $preset];
    }

    public function series(Builder $positive, Builder $negative, Carbon $from, Carbon $to, string $grain, string $amountSql = 'amount'): array
    {
        $format = match ($grain) {
            'year' => 'YYYY',
            'month' => 'YYYY-MM',
            default => 'YYYY-MM-DD',
        };
        $phpFormat = match ($grain) {
            'year' => 'Y',
            'month' => 'Y-m',
            default => 'Y-m-d',
        };

        $yes = $this->grouped($positive, $format, $amountSql);
        $no = $this->grouped($negative, $format, $amountSql);

        $labels = [];
        $positiveCounts = [];
        $negativeCounts = [];
        $positiveAmounts = [];
        $negativeAmounts = [];

        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->format($phpFormat);
            if ($labels === [] || end($labels) !== $key) {
                $labels[] = $key;
                $positiveCounts[] = (int) ($yes[$key]['total'] ?? 0);
                $negativeCounts[] = (int) ($no[$key]['total'] ?? 0);
                $positiveAmounts[] = (int) ($yes[$key]['amount'] ?? 0);
                $negativeAmounts[] = (int) ($no[$key]['amount'] ?? 0);
            }
            if ($grain === 'year') {
                $cursor->addYear();
            } elseif ($grain === 'month') {
                $cursor->addMonth();
            } else {
                $cursor->addDay();
            }
        }

        return [
            'labels' => $labels,
            'positive_counts' => $positiveCounts,
            'negative_counts' => $negativeCounts,
            'positive_amounts' => $positiveAmounts,
            'negative_amounts' => $negativeAmounts,
        ];
    }

    private function grouped(Builder $query, string $format, string $amountSql): array
    {
        $bucket = "to_char(payments.created_at, '{$format}')";
        $rows = (clone $query)->reorder()
            ->selectRaw($bucket.' as bucket')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(SUM('.$amountSql.'), 0) as amount')
            ->groupByRaw($bucket)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->bucket] = [
                'total' => (int) $row->total,
                'amount' => (int) $row->amount,
            ];
        }

        return $map;
    }
}
