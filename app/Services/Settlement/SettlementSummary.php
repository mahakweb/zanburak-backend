<?php

namespace App\Services\Settlement;

use App\Models\Course;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Settlement;
use App\Models\User;
use App\Services\Security\ContentScope;
use App\Support\SettlementStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettlementSummary
{
    public function snapshot(User $user, ?Carbon $from, ?Carbon $to): ?array
    {
        if (! $user->isSuperUser() && ! $user->contentScope()->canAction('settlements', 'view')) {
            return null;
        }

        $teacherId = $this->teacherConstraint($user);
        $share = TeacherShare::SQL;
        $items = $this->courseItems($user->contentScope(), $teacherId)
            ->leftJoin('settlement_items as si', function ($join) {
                $join->on('si.payment_item_id', '=', 'payment_items.id')
                    ->whereNull('si.released_at');
            })
            ->leftJoin('settlements as s', 's.id', '=', 'si.settlement_id');

        if ($from && $to) {
            $items->whereBetween('payments.paid_at', [$from, $to]);
        }

        $row = $items->selectRaw("
            COALESCE(SUM({$share}), 0) as share,
            COALESCE(SUM(CASE WHEN s.status = 'settled' THEN {$share} ELSE 0 END), 0) as settled_amount,
            COALESCE(SUM(CASE WHEN s.status = 'settled' THEN 1 ELSE 0 END), 0) as settled_count,
            COALESCE(SUM(CASE WHEN s.id IS NULL OR s.status <> 'settled' THEN {$share} ELSE 0 END), 0) as unsettled_amount,
            COALESCE(SUM(CASE WHEN s.id IS NULL OR s.status <> 'settled' THEN 1 ELSE 0 END), 0) as unsettled_count
        ")->first();

        return [
            'settled_amount' => (int) ($row->settled_amount ?? 0),
            'settled_count' => (int) ($row->settled_count ?? 0),
            'unsettled_amount' => (int) ($row->unsettled_amount ?? 0),
            'unsettled_count' => (int) ($row->unsettled_count ?? 0),
            'share' => (int) ($row->share ?? 0),
        ];
    }

    public function dashboard(User $user, ?Carbon $from, ?Carbon $to): array
    {
        $teacherId = $this->teacherConstraint($user);
        $scope = $user->contentScope();

        $paid = $scope->applyToPayments(
            Payment::query()->where('status', true)->whereNotNull('paid_at')
        );
        if ($from && $to) {
            $paid->whereBetween('paid_at', [$from, $to]);
        }

        $share = $this->courseItems($scope, $teacherId);
        if ($from && $to) {
            $share->whereBetween('payments.paid_at', [$from, $to]);
        }

        $settled = Settlement::query()->where('status', SettlementStatus::SETTLED);
        $this->constrainSettlements($settled, $teacherId);
        if ($from && $to) {
            $settled->whereBetween('paid_at', [$from, $to]);
        }

        $outstanding = $this->outstanding($scope, $teacherId);

        return [
            'period' => [
                'from' => $from?->toDateTimeString(),
                'to' => $to?->toDateTimeString(),
            ],
            'sales_count' => (int) (clone $paid)->count(),
            'sales_amount' => (int) (clone $paid)->sum('amount'),
            'teacher_share' => (int) (clone $share)->sum(DB::raw(TeacherShare::SQL)),
            'gateway_fee' => (int) (clone $share)->sum(DB::raw('COALESCE(payment_items.gateway_fee_amount, 0)')),
            'settled_amount' => (int) (clone $settled)->sum('amount'),
            'settled_count' => (int) (clone $settled)->count(),
            'awaiting_amount' => $outstanding['awaiting_amount'],
            'awaiting_count' => $outstanding['awaiting_count'],
            'processing_amount' => $outstanding['processing_amount'],
            'outstanding_amount' => $outstanding['outstanding_amount'],
            'outstanding_count' => $outstanding['outstanding_count'],
            'teachers_with_balance' => $outstanding['teachers'],
            'share_basis' => 'payment_items.final_price',
        ];
    }

    public function teachers(User $user, Request $request)
    {
        $teacherId = $this->teacherConstraint($user);
        $share = TeacherShare::SQL;

        $query = $this->courseItems($user->contentScope(), $teacherId)
            ->leftJoin('settlement_items as si', function ($join) {
                $join->on('si.payment_item_id', '=', 'payment_items.id')
                    ->whereNull('si.released_at');
            })
            ->leftJoin('settlements as s', 's.id', '=', 'si.settlement_id')
            ->join('users', 'users.id', '=', 'courses.teacher_id')
            ->select([
                'users.id as teacher_id',
                'users.first_name',
                'users.last_name',
                'users.username',
                DB::raw('COUNT(DISTINCT payments.id) as sales_count'),
                DB::raw("SUM({$share}) as teacher_share"),
                DB::raw("COALESCE(SUM(CASE WHEN s.status = 'settled' THEN si.amount ELSE 0 END), 0) as settled_amount"),
                DB::raw("SUM(CASE WHEN s.id IS NULL OR s.status <> 'settled' THEN {$share} ELSE 0 END) as pending_amount"),
                DB::raw("SUM(CASE WHEN s.id IS NULL OR s.status <> 'settled' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("MAX(CASE WHEN s.status = 'settled' THEN s.paid_at END) as last_settled_at"),
            ])
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.username');

        if ($request->filled('teacher_id')) {
            $query->where('users.id', (int) $request->input('teacher_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($inner) use ($search) {
                $inner->where('users.first_name', 'like', "%{$search}%")
                    ->orWhere('users.last_name', 'like', "%{$search}%")
                    ->orWhere('users.username', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('with_balance')) {
            $query->havingRaw("SUM(CASE WHEN s.id IS NULL OR s.status <> 'settled' THEN {$share} ELSE 0 END) > 0");
        }

        $sort = $request->input('sort', 'pending');
        if ($sort === 'share') {
            $query->orderByDesc('teacher_share');
        } elseif ($sort === 'sales') {
            $query->orderByDesc('sales_count');
        } else {
            $query->orderByDesc('pending_amount');
        }

        return $query->paginate((int) $request->input('perPage', 20));
    }

    public function teacher(User $user, int $teacherId): array
    {
        $constraint = $this->teacherConstraint($user);
        if ($constraint === -1 || ($constraint && $constraint !== $teacherId)) {
            abort(403, 'به اطلاعات مالی این مدرس دسترسی ندارید.');
        }

        $row = $this->teachers($user, new Request(['teacher_id' => $teacherId, 'perPage' => 1]))
            ->getCollection()
            ->first();

        if (! $row) {
            $teacher = User::query()->findOrFail($teacherId);

            return [
                'teacher_id' => $teacher->id,
                'name' => trim($teacher->first_name.' '.$teacher->last_name),
                'sales_count' => 0,
                'teacher_share' => 0,
                'settled_amount' => 0,
                'pending_amount' => 0,
                'pending_count' => 0,
                'last_settled_at' => null,
            ];
        }

        return $this->mapTeacher($row);
    }

    public function mapTeacher(object $row): array
    {
        return [
            'teacher_id' => (int) $row->teacher_id,
            'name' => trim(($row->first_name ?? '').' '.($row->last_name ?? '')),
            'username' => $row->username ?? null,
            'sales_count' => (int) $row->sales_count,
            'teacher_share' => (int) $row->teacher_share,
            'settled_amount' => (int) $row->settled_amount,
            'pending_amount' => (int) $row->pending_amount,
            'pending_count' => (int) $row->pending_count,
            'last_settled_at' => $row->last_settled_at,
        ];
    }

    public function teacherConstraint(User $user): ?int
    {
        if ($user->isSuperUser()) {
            return null;
        }

        $scope = $user->contentScope();
        if ($scope->viewScope('payments') === ContentScope::ANY || $scope->viewScope('settlements') === ContentScope::ANY) {
            return null;
        }

        if ($user->hasAnyPermissionName(['settlements.view_all_teachers'])) {
            return null;
        }

        if ($scope->viewScope('payments') === ContentScope::OWN || $scope->viewScope('settlements') === ContentScope::OWN) {
            return (int) $user->id;
        }

        return -1;
    }

    private function outstanding(ContentScope $scope, ?int $teacherId): array
    {
        $share = TeacherShare::SQL;
        $row = $this->courseItems($scope, $teacherId)
            ->leftJoin('settlement_items as si', function ($join) {
                $join->on('si.payment_item_id', '=', 'payment_items.id')
                    ->whereNull('si.released_at');
            })
            ->leftJoin('settlements as s', 's.id', '=', 'si.settlement_id')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN s.id IS NULL THEN {$share} ELSE 0 END), 0) as awaiting_amount,
                COALESCE(SUM(CASE WHEN s.id IS NULL THEN 1 ELSE 0 END), 0) as awaiting_count,
                COALESCE(SUM(CASE WHEN s.status = 'processing' THEN {$share} ELSE 0 END), 0) as processing_amount,
                COALESCE(SUM(CASE WHEN s.id IS NULL OR s.status <> 'settled' THEN {$share} ELSE 0 END), 0) as outstanding_amount,
                COALESCE(SUM(CASE WHEN s.id IS NULL OR s.status <> 'settled' THEN 1 ELSE 0 END), 0) as outstanding_count,
                COUNT(DISTINCT CASE WHEN s.id IS NULL OR s.status <> 'settled' THEN courses.teacher_id END) as teachers
            ")
            ->first();

        return [
            'awaiting_amount' => (int) ($row->awaiting_amount ?? 0),
            'awaiting_count' => (int) ($row->awaiting_count ?? 0),
            'processing_amount' => (int) ($row->processing_amount ?? 0),
            'outstanding_amount' => (int) ($row->outstanding_amount ?? 0),
            'outstanding_count' => (int) ($row->outstanding_count ?? 0),
            'teachers' => (int) ($row->teachers ?? 0),
        ];
    }

    private function courseItems(ContentScope $scope, ?int $teacherId)
    {
        $query = PaymentItem::query()
            ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
            ->join('courses', function ($join) {
                $join->on('courses.id', '=', 'payment_items.payable_id')
                    ->where('payment_items.payable_type', Course::class);
            })
            ->where('payments.status', true)
            ->whereNotNull('payments.paid_at')
            ->whereNotNull('courses.teacher_id')
            ->whereRaw(TeacherShare::SQL.' > 0');

        $scope->constrainJoinedPayments($query, 'payments');

        if ($teacherId === -1) {
            $query->whereRaw('1 = 0');
        } elseif ($teacherId) {
            $query->where('courses.teacher_id', $teacherId);
        }

        return $query;
    }

    private function constrainSettlements($query, ?int $teacherId): void
    {
        if ($teacherId === -1) {
            $query->whereRaw('1 = 0');
        } elseif ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
    }
};
