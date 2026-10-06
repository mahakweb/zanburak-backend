<?php

namespace App\Services\Settlement;

use App\Models\Course;
use App\Support\SettlementStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PaymentSettlementFilters
{
    public static function apply(Builder $query, Request $request, ?int $onlyTeacherId): void
    {
        $teacherId = $onlyTeacherId;
        if ($request->filled('teacher_id')) {
            $requested = (int) $request->input('teacher_id');
            if ($onlyTeacherId && $requested !== $onlyTeacherId) {
                $query->whereRaw('1 = 0');

                return;
            }
            $teacherId = $onlyTeacherId ?: $requested;
        }

        if ($teacherId) {
            $courseIds = Course::query()->where('teacher_id', $teacherId)->select('id');
            $query->whereHas('items', function (Builder $items) use ($courseIds) {
                $items->where('payable_type', Course::class)->whereIn('payable_id', $courseIds);
            });
        }

        if ($request->filled('course_id')) {
            $courseId = (int) $request->input('course_id');
            $query->whereHas('items', function (Builder $items) use ($courseId) {
                $items->where('payable_type', Course::class)->where('payable_id', $courseId);
            });
        }

        if ($request->filled('buyer')) {
            $buyer = $request->input('buyer');
            $query->whereHas('user', function (Builder $user) use ($buyer) {
                $user->where('first_name', 'like', "%{$buyer}%")
                    ->orWhere('last_name', 'like', "%{$buyer}%")
                    ->orWhere('username', 'like', "%{$buyer}%")
                    ->orWhere('email', 'like', "%{$buyer}%");
            });
        }

        if ($request->filled('date_preset') || ($request->filled('paid_from') && $request->filled('paid_to'))) {
            [$from, $to] = SettlementPeriod::fromRequest($request);
            if ($from && $to) {
                $query->whereBetween('paid_at', [$from, $to]);
            }
        }

        $status = $request->boolean('needs_settlement')
            ? SettlementStatus::PENDING
            : (string) $request->input('settlement_status', '');

        if ($status !== '') {
            self::whereStatus($query, $status, $teacherId);
        }
    }

    private static function whereStatus(Builder $query, string $status, ?int $teacherId): void
    {
        if ($status === SettlementStatus::PENDING) {
            $query->where('status', true)->whereNotNull('paid_at');
            $query->whereExists(function ($sub) use ($teacherId) {
                self::eligibleItems($sub, $teacherId, false);
            });

            return;
        }

        if ($status === SettlementStatus::NOT_APPLICABLE) {
            $query->where(function (Builder $inner) use ($teacherId) {
                $inner->where(function (Builder $unpaid) {
                    $unpaid->where('status', false)->orWhereNull('paid_at');
                })->orWhereNotExists(function ($sub) use ($teacherId) {
                    self::eligibleItems($sub, $teacherId, null);
                });
            });

            return;
        }

        if (in_array($status, [SettlementStatus::PROCESSING, SettlementStatus::SETTLED], true)) {
            $query->whereExists(function ($sub) use ($teacherId, $status) {
                self::activeSettlement($sub, $teacherId, $status);
            });
            if ($status === SettlementStatus::SETTLED) {
                $query->whereNotExists(function ($sub) use ($teacherId) {
                    self::eligibleItems($sub, $teacherId, false);
                });
            }

            return;
        }

        if (in_array($status, [SettlementStatus::CANCELLED, SettlementStatus::REVERSED], true)) {
            $query->whereExists(function ($sub) use ($teacherId, $status) {
                $sub->selectRaw('1')
                    ->from('settlement_items')
                    ->join('settlements', 'settlements.id', '=', 'settlement_items.settlement_id')
                    ->whereColumn('settlement_items.payment_id', 'payments.id')
                    ->where('settlements.status', $status);
                if ($teacherId) {
                    $sub->where('settlement_items.teacher_id', $teacherId);
                }
            });
        }
    }

    private static function eligibleItems($sub, ?int $teacherId, ?bool $withoutActive): void
    {
        $sub->selectRaw('1')
            ->from('payment_items')
            ->join('courses', function ($join) {
                $join->on('courses.id', '=', 'payment_items.payable_id')
                    ->where('payment_items.payable_type', Course::class);
            })
            ->whereColumn('payment_items.payment_id', 'payments.id')
            ->whereNotNull('courses.teacher_id')
            ->whereRaw(TeacherShare::sql().' > 0');

        if ($teacherId) {
            $sub->where('courses.teacher_id', $teacherId);
        }

        if ($withoutActive === false) {
            $sub->whereNotExists(function ($active) {
                $active->selectRaw('1')
                    ->from('settlement_items')
                    ->whereColumn('settlement_items.payment_item_id', 'payment_items.id')
                    ->whereNull('settlement_items.released_at');
            });
        }
    }

    private static function activeSettlement($sub, ?int $teacherId, string $status): void
    {
        $sub->selectRaw('1')
            ->from('settlement_items')
            ->join('settlements', 'settlements.id', '=', 'settlement_items.settlement_id')
            ->whereColumn('settlement_items.payment_id', 'payments.id')
            ->whereNull('settlement_items.released_at')
            ->where('settlements.status', $status);
        if ($teacherId) {
            $sub->where('settlement_items.teacher_id', $teacherId);
        }
    }
};
