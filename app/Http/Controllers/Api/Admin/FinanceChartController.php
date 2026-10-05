<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AppliesContentScope;
use App\Models\Payment;
use App\Services\Security\ContentScope;
use App\Services\Settlement\SettlementLedger;
use App\Services\Settlement\StatusTrend;
use Illuminate\Http\Request;

class FinanceChartController extends Controller
{
    use AppliesContentScope;

    public function __construct(
        private StatusTrend $trend,
        private SettlementLedger $ledger,
    ) {
    }

    public function payments(Request $request)
    {
        $user = $request->user();
        if ($user->contentScope()->viewScope('payments') === ContentScope::NONE) {
            abort(403, 'اجازه مشاهده پرداخت‌ها را ندارید.');
        }

        [$from, $to, $grain, $preset] = $this->trend->period($request);
        $base = $this->contentScope()->applyToPayments(Payment::query())
            ->whereBetween('payments.created_at', [$from, $to]);

        $paid = (clone $base)->where('payments.status', true)->whereNotNull('payments.paid_at');
        $unpaid = (clone $base)->where(function ($query) {
            $query->where('payments.status', false)->orWhereNull('payments.paid_at');
        });

        return response()->json([
            'message' => 'Success',
            'preset' => $preset,
            'grain' => $grain,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'series' => $this->trend->series($paid, $unpaid, $from, $to, $grain, 'payments.amount'),
        ]);
    }

    public function settlements(Request $request)
    {
        $user = $request->user();
        $scope = $user->contentScope();
        if ($scope->viewScope('payments') === ContentScope::NONE && $scope->viewScope('settlements') === ContentScope::NONE) {
            abort(403, 'اجازه مشاهده تسویه‌ها را ندارید.');
        }

        [$from, $to, $grain, $preset] = $this->trend->period($request);
        $teacherId = $this->ledger->teacherConstraint($user);
        $scopeTeacher = $teacherId && $teacherId > 0 ? $teacherId : null;
        $base = $this->ledger->query($user)->whereBetween('payments.created_at', [$from, $to]);

        $settled = clone $base;
        $unsettled = clone $base;
        $this->ledger->applyStatus($settled, $scopeTeacher, true);
        $this->ledger->applyStatus($unsettled, $scopeTeacher, false);

        return response()->json([
            'message' => 'Success',
            'preset' => $preset,
            'grain' => $grain,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'series' => $this->trend->series(
                $settled,
                $unsettled,
                $from,
                $to,
                $grain,
                'COALESCE(NULLIF(payments.base_amount, 0), payments.amount, 0)'
            ),
        ]);
    }
}
