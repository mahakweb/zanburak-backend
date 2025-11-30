<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Plan;
use App\Models\Path;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $groupBy = $request->input('group_by', 'day'); // day | week | month

        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->endOfDay();

        // Align and prepare labels based on group_by
        if ($groupBy === 'week') {
            $start = $start->copy()->startOfWeek(Carbon::MONDAY);
            $end = $end->copy()->endOfWeek(Carbon::SUNDAY);
            $period = CarbonPeriod::create($start, '1 week', $end);
            $labelDates = collect($period)->map(fn ($d) => $d->copy()->startOfWeek(Carbon::MONDAY)->toDateString())->values();
            $groupSelect = "DATE(DATE_SUB(%s, INTERVAL (WEEKDAY(%s)) DAY))";
        } elseif ($groupBy === 'month') {
            $start = $start->copy()->startOfMonth();
            $end = $end->copy()->endOfMonth();
            $period = CarbonPeriod::create($start, '1 month', $end);
            $labelDates = collect($period)->map(fn ($d) => $d->copy()->startOfMonth()->toDateString())->values();
            $groupSelect = "DATE_FORMAT(%s, '%%Y-%%m-01')";
        } else {
            // day
            $period = CarbonPeriod::create($start, '1 day', $end);
            $labelDates = collect($period)->map(fn ($d) => $d->toDateString())->values();
            $groupSelect = "DATE(%s)";
        }

        // Users
        $usersDailyRaw = User::whereBetween('created_at', [$start, $end])
            ->select(DB::raw(sprintf($groupSelect, 'created_at', 'created_at') . ' as grp'), DB::raw('COUNT(*) as count'))
            ->groupBy('grp')
            ->orderBy('grp')
            ->pluck('count', 'grp');

        $usersDaily = $labelDates->map(fn ($date) => (int) ($usersDailyRaw[$date] ?? 0));
        $totalUsers = User::count();
        $activeUsers24h = User::where('last_seen', '>=', now()->subDay())->count();
        $registrationsInPeriod = $usersDaily->sum();

        // Comments
        $commentsDailyRaw = Comment::whereBetween('created_at', [$start, $end])
            ->select(DB::raw(sprintf($groupSelect, 'created_at', 'created_at') . ' as grp'), DB::raw('COUNT(*) as count'))
            ->groupBy('grp')
            ->orderBy('grp')
            ->pluck('count', 'grp');

        $commentsDaily = $labelDates->map(fn ($date) => (int) ($commentsDailyRaw[$date] ?? 0));
        $totalComments = Comment::count();
        $approvedComments = Comment::where('approved', 1)->count();
        $unapprovedComments = Comment::where('approved', 0)->count();
        $commentsInPeriod = $commentsDaily->sum();

        // Courses
        $totalCourses = Course::count();

        // Payments / Sales (mutually exclusive buckets)
        $basePayments = Payment::query()->whereBetween('created_at', [$start, $end]);
        $paidPayments = Payment::query()
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at');
        $expiredPayments = Payment::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 0)
            ->whereNull('paid_at')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now());
        $pendingPayments = Payment::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 0)
            ->whereNull('paid_at')
            ->where(function ($q) {
                $q->whereNull('expired_at')
                    ->orWhere('expired_at', '>=', now());
            });

        $paymentsStats = [
            'total_payments' => (int) $basePayments->count(),
            'paid_payments' => (int) $paidPayments->count(),
            'pending_payments' => (int) $pendingPayments->count(),
            'expired_payments' => (int) $expiredPayments->count(),
            'total_amount' => (int) $paidPayments->sum('amount'),
            'total_discount' => (int) $paidPayments->sum('discount_amount'),
        ];
        $paymentsStats['conversion_rate'] = $paymentsStats['total_payments'] > 0
            ? round(($paymentsStats['paid_payments'] / $paymentsStats['total_payments']) * 100, 2)
            : 0;
        $paymentsStats['arpu'] = $paymentsStats['paid_payments'] > 0
            ? (int) floor($paymentsStats['total_amount'] / $paymentsStats['paid_payments'])
            : 0;
        $paymentsStats['aov'] = $paymentsStats['arpu']; // If each paid payment is an order

        // Payment methods breakdown
        $paymentMethods = Payment::whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->select('driver', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('driver')
            ->get();

        // Daily paid payments (amount)
        $dailyPaymentsAmountRaw = Payment::whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->select(DB::raw(sprintf($groupSelect, 'paid_at', 'paid_at') . ' as grp'), DB::raw('sum(amount) as total'))
            ->groupBy('grp')
            ->orderBy('grp')
            ->pluck('total', 'grp');

        $dailyPaymentsAmount = $labelDates->map(fn ($date) => (int) ($dailyPaymentsAmountRaw[$date] ?? 0));

        // Daily paid payments (count)
        $dailyPaymentsCountRaw = Payment::whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->select(DB::raw(sprintf($groupSelect, 'paid_at', 'paid_at') . ' as grp'), DB::raw('count(*) as count'))
            ->groupBy('grp')
            ->orderBy('grp')
            ->pluck('count', 'grp');

        $dailyPaymentsCount = $labelDates->map(fn ($date) => (int) ($dailyPaymentsCountRaw[$date] ?? 0));

        // Recent items (limited to period)
        $recentPayments = Payment::with(['user:id,first_name,last_name,username,profile_pic'])
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->orderByDesc('paid_at')
            ->take(10)
            ->get(['id', 'user_id', 'amount', 'discount_amount', 'status', 'driver', 'paid_at', 'created_at']);

        $recentUsers = User::whereBetween('created_at', [$start, $end])
            ->latest('id')
            ->take(8)
            ->get(['id', 'first_name', 'last_name', 'username', 'profile_pic', 'created_at']);

        $recentComments = Comment::with(['user:id,first_name,last_name,username,profile_pic'])
            ->whereBetween('created_at', [$start, $end])
            ->latest('id')
            ->take(8)
            ->get(['id', 'user_id', 'comment', 'approved', 'created_at']);

        // Paying users (unique) in period
        $uniquePayingUsers = Payment::whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->distinct('user_id')
            ->count('user_id');

        // ARPPU (average revenue per paying user)
        $arppu = $uniquePayingUsers > 0 ? (int) floor($paymentsStats['total_amount'] / $uniquePayingUsers) : 0;

        // New vs Returning (based on user created_at)
        $paidUserIds = Payment::whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->pluck('user_id')
            ->unique();
        // returning customers are those who had a paid transaction before this period
        $returningCustomers = Payment::where('status', 1)
            ->whereNotNull('paid_at')
            ->where('paid_at', '<', $start)
            ->whereIn('user_id', $paidUserIds)
            ->distinct('user_id')
            ->count('user_id');
        $newCustomers = max($uniquePayingUsers - $returningCustomers, 0);

        // Payment status distribution in period
        $statusDistribution = [
            'paid' => (int) $paymentsStats['paid_payments'],
            'pending' => (int) $paymentsStats['pending_payments'],
            'expired' => (int) $paymentsStats['expired_payments'],
        ];

        // Revenue breakdown by product type using payment_items
        $basePaidItems = PaymentItem::query()
            ->select('payment_items.payable_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(payment_items.final_price, payment_items.price)) as total'))
            ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
            ->whereBetween('payments.paid_at', [$start, $end])
            ->where('payments.status', 1)
            ->whereNotNull('payments.paid_at')
            ->groupBy('payment_items.payable_type');
        $revenueByType = $basePaidItems->get()->map(function ($row) {
            $type = 'other';
            if ($row->payable_type === Course::class) $type = 'course';
            if ($row->payable_type === Path::class) $type = 'path';
            if ($row->payable_type === Plan::class) $type = 'vip';
            return ['type' => $type, 'count' => (int) $row->count, 'total' => (int) $row->total];
        });

        // Top products (courses, paths, plans)
        $topCourses = PaymentItem::query()
            ->select('payment_items.payable_id', DB::raw('SUM(COALESCE(payment_items.final_price, payment_items.price)) as total'), DB::raw('COUNT(*) as count'))
            ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
            ->where('payment_items.payable_type', Course::class)
            ->whereBetween('payments.paid_at', [$start, $end])
            ->where('payments.status', 1)
            ->whereNotNull('payments.paid_at')
            ->groupBy('payment_items.payable_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function ($row) {
                $course = Course::find($row->payable_id, ['id', 'title', 'slug']);
                return [
                    'id' => $row->payable_id,
                    'title' => $course?->title,
                    'slug' => $course?->slug,
                    'total' => (int) $row->total,
                    'count' => (int) $row->count,
                ];
            })
            ->filter(fn ($x) => !is_null($x['title']))
            ->values();

        $topPaths = PaymentItem::query()
            ->select('payment_items.payable_id', DB::raw('SUM(COALESCE(payment_items.final_price, payment_items.price)) as total'), DB::raw('COUNT(*) as count'))
            ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
            ->where('payment_items.payable_type', Path::class)
            ->whereBetween('payments.paid_at', [$start, $end])
            ->where('payments.status', 1)
            ->whereNotNull('payments.paid_at')
            ->groupBy('payment_items.payable_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function ($row) {
                $path = Path::find($row->payable_id, ['id', 'title', 'slug']);
                return [
                    'id' => $row->payable_id,
                    'title' => $path?->title,
                    'slug' => $path?->slug,
                    'total' => (int) $row->total,
                    'count' => (int) $row->count,
                ];
            })
            ->filter(fn ($x) => !is_null($x['title']))
            ->values();

        $topPlans = PaymentItem::query()
            ->select('payment_items.payable_id', DB::raw('SUM(COALESCE(payment_items.final_price, payment_items.price)) as total'), DB::raw('COUNT(*) as count'))
            ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
            ->where('payment_items.payable_type', Plan::class)
            ->whereBetween('payments.paid_at', [$start, $end])
            ->where('payments.status', 1)
            ->whereNotNull('payments.paid_at')
            ->groupBy('payment_items.payable_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function ($row) {
                $plan = Plan::find($row->payable_id, ['id', 'title', 'english_title']);
                return [
                    'id' => $row->payable_id,
                    'title' => $plan?->title,
                    'english_title' => $plan?->english_title,
                    'total' => (int) $row->total,
                    'count' => (int) $row->count,
                ];
            })
            ->filter(fn ($x) => !is_null($x['title']))
            ->values();

        // Discount usage
        $discountsUsage = PaymentItem::query()
            ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
            ->whereBetween('payments.paid_at', [$start, $end])
            ->where('payments.status', 1)
            ->whereNotNull('payments.paid_at')
            ->whereNotNull('payment_items.discount_code')
            ->select('payment_items.discount_code', DB::raw('COUNT(DISTINCT payment_items.payment_id) as used_times'), DB::raw('SUM(payment_items.discount_amount) as total_discount'))
            ->groupBy('payment_items.discount_code')
            ->orderByDesc('used_times')
            ->take(5)
            ->get();
        $totalDiscountUses = $discountsUsage->sum('used_times');

        return response()->json([
            'message' => 'Success',
            'period' => [
                'date_from' => $start->toDateString(),
                'date_to' => $end->toDateString(),
                'labels' => $labelDates,
                'group_by' => $groupBy,
            ],
            'users' => [
                'total' => $totalUsers,
                'active_24h' => $activeUsers24h,
                'in_period' => $registrationsInPeriod,
                'daily' => [
                    'labels' => $labelDates,
                    'data' => $usersDaily,
                ],
                'recent' => $recentUsers,
            ],
            'comments' => [
                'total' => $totalComments,
                'approved' => $approvedComments,
                'unapproved' => $unapprovedComments,
                'in_period' => $commentsInPeriod,
                'daily' => [
                    'labels' => $labelDates,
                    'data' => $commentsDaily,
                ],
                'recent' => $recentComments,
            ],
            'courses' => [
                'total' => $totalCourses,
            ],
            'payments' => [
                'summary' => $paymentsStats,
                'daily' => [
                    'labels' => $labelDates,
                    'amount' => $dailyPaymentsAmount,
                    'count' => $dailyPaymentsCount,
                ],
                'methods' => $paymentMethods,
                'recent' => $recentPayments,
                'statuses' => $statusDistribution,
                'revenue_by_type' => $revenueByType,
            ],
            'insights' => [
                'paying_users' => [
                    'unique' => $uniquePayingUsers,
                    'arppu' => $arppu,
                ],
                'customers' => [
                    'new' => $newCustomers,
                    'returning' => $returningCustomers,
                ],
                'top_products' => [
                    'courses' => $topCourses,
                    'paths' => $topPaths,
                    'plans' => $topPlans,
                ],
                'discounts' => [
                    'total_used' => (int) $totalDiscountUses,
                    'top_codes' => $discountsUsage,
                ],
            ],
        ], 200);
    }

    public function unapprovedCommentsCount()
    {
        $count = Comment::where('approved', 0)->count();
        
        return response()->json([
            'message' => 'Success',
            'count' => $count,
        ]);
    }
}


