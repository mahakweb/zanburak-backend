<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AppliesContentScope;
use App\Models\Answer;
use App\Models\Article;
use App\Models\Certificate;
use App\Models\User;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Plan;
use App\Models\Path;
use App\Models\Question;
use App\Models\UserLogin;
use App\Models\VideoView;
use App\Models\View;
use App\Models\Like;
use App\Support\SqlDialect;
use App\Services\Security\ContentScope;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LaravelInteraction\Bookmark\Bookmark;

class DashboardController extends Controller
{
    use AppliesContentScope;

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
        } elseif ($groupBy === 'month') {
            $start = $start->copy()->startOfMonth();
            $end = $end->copy()->endOfMonth();
            $period = CarbonPeriod::create($start, '1 month', $end);
            $labelDates = collect($period)->map(fn ($d) => $d->copy()->startOfMonth()->toDateString())->values();
        } else {
            // day
            $period = CarbonPeriod::create($start, '1 day', $end);
            $labelDates = collect($period)->map(fn ($d) => $d->toDateString())->values();
        }

        $groupExpr = fn (string $column) => SqlDialect::groupByPeriod($column, $groupBy);
        $scope = $this->contentScope();
        $canGlobalUsers = $scope->canViewGlobalUserMetrics();

        // Users
        if ($canGlobalUsers) {
            $usersDailyRaw = User::whereBetween('created_at', [$start, $end])
                ->select(DB::raw($groupExpr('created_at').' as grp'), DB::raw('COUNT(*) as count'))
                ->groupBy('grp')
                ->orderBy('grp')
                ->pluck('count', 'grp');

            $usersDaily = $labelDates->map(fn ($date) => (int) ($usersDailyRaw[$date] ?? 0));
            $totalUsers = User::count();
            $activeUsers24h = User::where('last_seen', '>=', now()->subDay())->count();
            $registrationsInPeriod = $usersDaily->sum();
            $recentUsers = User::whereBetween('created_at', [$start, $end])
                ->latest('id')
                ->take(8)
                ->get(['id', 'first_name', 'last_name', 'username', 'profile_pic', 'created_at']);
        } else {
            $usersDaily = $this->emptyDailySeries($labelDates);
            $totalUsers = 0;
            $activeUsers24h = 0;
            $registrationsInPeriod = 0;
            $recentUsers = collect();
        }

        // Comments
        $commentsDailyRaw = $this->scopedCommentsQuery()
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw($groupExpr('created_at').' as grp'), DB::raw('COUNT(*) as count'))
            ->groupBy('grp')
            ->orderBy('grp')
            ->pluck('count', 'grp');

        $commentsDaily = $labelDates->map(fn ($date) => (int) ($commentsDailyRaw[$date] ?? 0));
        $totalComments = (int) $this->scopedCommentsQuery()->count();
        $approvedComments = (int) $this->scopedCommentsQuery()->where('approved', 1)->count();
        $unapprovedComments = (int) $this->scopedCommentsQuery()->where('approved', 0)->count();
        $commentsInPeriod = $commentsDaily->sum();

        // Courses
        $totalCourses = (int) $this->scopedCoursesQuery()->count();

        // Payments / Sales (mutually exclusive buckets)
        $basePayments = $this->scopedPaymentsQuery()->whereBetween('created_at', [$start, $end]);
        $paidPayments = $this->scopedPaymentsQuery()
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at');
        $expiredPayments = $this->scopedPaymentsQuery()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 0)
            ->whereNull('paid_at')
            ->whereNotNull('expired_at')
            ->where('expired_at', '<', now());
        $pendingPayments = $this->scopedPaymentsQuery()
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
        $paymentMethods = $this->scopedPaymentsQuery()
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->select('driver', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('driver')
            ->get();

        // Daily paid payments (amount)
        $dailyPaymentsAmountRaw = $this->scopedPaymentsQuery()
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->select(DB::raw($groupExpr('paid_at').' as grp'), DB::raw('sum(amount) as total'))
            ->groupBy('grp')
            ->orderBy('grp')
            ->pluck('total', 'grp');

        $dailyPaymentsAmount = $labelDates->map(fn ($date) => (int) ($dailyPaymentsAmountRaw[$date] ?? 0));

        // Daily paid payments (count)
        $dailyPaymentsCountRaw = $this->scopedPaymentsQuery()
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->select(DB::raw($groupExpr('paid_at').' as grp'), DB::raw('count(*) as count'))
            ->groupBy('grp')
            ->orderBy('grp')
            ->pluck('count', 'grp');

        $dailyPaymentsCount = $labelDates->map(fn ($date) => (int) ($dailyPaymentsCountRaw[$date] ?? 0));

        // Recent items (limited to period)
        $recentPayments = $this->scopedPaymentsQuery()
            ->with(['user:id,first_name,last_name,username,profile_pic'])
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->orderByDesc('paid_at')
            ->take(10)
            ->get(['id', 'user_id', 'amount', 'discount_amount', 'status', 'driver', 'paid_at', 'created_at']);

        $recentComments = $this->scopedCommentsQuery()
            ->with(['user:id,first_name,last_name,username,profile_pic'])
            ->whereBetween('created_at', [$start, $end])
            ->latest('id')
            ->take(8)
            ->get(['id', 'user_id', 'comment', 'approved', 'created_at']);

        // Paying users (unique) in period
        $uniquePayingUsers = $this->scopedPaymentsQuery()
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->distinct('user_id')
            ->count('user_id');

        // ARPPU (average revenue per paying user)
        $arppu = $uniquePayingUsers > 0 ? (int) floor($paymentsStats['total_amount'] / $uniquePayingUsers) : 0;

        // New vs Returning (based on user created_at)
        $paidUserIds = $this->scopedPaymentsQuery()
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->pluck('user_id')
            ->unique();
        // returning customers are those who had a paid transaction before this period
        $returningCustomers = $this->scopedPaymentsQuery()
            ->where('status', 1)
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
        $basePaidItems = $scope->constrainJoinedPayments(
            PaymentItem::query()
            ->select('payment_items.payable_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(COALESCE(payment_items.final_price, payment_items.price)) as total'))
            ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
            ->whereBetween('payments.paid_at', [$start, $end])
            ->where('payments.status', 1)
            ->whereNotNull('payments.paid_at')
            ->groupBy('payment_items.payable_type')
        );
        $revenueByType = $basePaidItems->get()->map(function ($row) {
            $type = 'other';
            if ($row->payable_type === Course::class) $type = 'course';
            if ($row->payable_type === Path::class) $type = 'path';
            if ($row->payable_type === Plan::class) $type = 'vip';
            return ['type' => $type, 'count' => (int) $row->count, 'total' => (int) $row->total];
        });

        // Top products (courses, paths, plans)
        $topCourses = $scope->constrainJoinedPayments(
            PaymentItem::query()
            ->select('payment_items.payable_id', DB::raw('SUM(COALESCE(payment_items.final_price, payment_items.price)) as total'), DB::raw('COUNT(*) as count'))
            ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
            ->where('payment_items.payable_type', Course::class)
            ->whereBetween('payments.paid_at', [$start, $end])
            ->where('payments.status', 1)
            ->whereNotNull('payments.paid_at')
            ->groupBy('payment_items.payable_id')
            ->orderByDesc('total')
            ->take(5)
        )->get()
            ->map(function ($row) use ($scope) {
                $course = $scope->applyToCourses(Course::query()->where('id', $row->payable_id))->first(['id', 'title', 'slug']);
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

        $topPaths = $scope->viewScope(ContentScope::ANY) === ContentScope::ANY
            ? $scope->constrainJoinedPayments(
                PaymentItem::query()
                ->select('payment_items.payable_id', DB::raw('SUM(COALESCE(payment_items.final_price, payment_items.price)) as total'), DB::raw('COUNT(*) as count'))
                ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
                ->where('payment_items.payable_type', Path::class)
                ->whereBetween('payments.paid_at', [$start, $end])
                ->where('payments.status', 1)
                ->whereNotNull('payments.paid_at')
                ->groupBy('payment_items.payable_id')
                ->orderByDesc('total')
                ->take(5)
            )->get()
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
                ->values()
            : collect();

        $topPlans = $scope->viewScope(ContentScope::ANY) === ContentScope::ANY
            ? $scope->constrainJoinedPayments(
                PaymentItem::query()
                ->select('payment_items.payable_id', DB::raw('SUM(COALESCE(payment_items.final_price, payment_items.price)) as total'), DB::raw('COUNT(*) as count'))
                ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
                ->where('payment_items.payable_type', Plan::class)
                ->whereBetween('payments.paid_at', [$start, $end])
                ->where('payments.status', 1)
                ->whereNotNull('payments.paid_at')
                ->groupBy('payment_items.payable_id')
                ->orderByDesc('total')
                ->take(5)
            )->get()
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
                ->values()
            : collect();

        // Discount usage
        $discountsUsage = $scope->constrainJoinedPayments(
            PaymentItem::query()
            ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
            ->whereBetween('payments.paid_at', [$start, $end])
            ->where('payments.status', 1)
            ->whereNotNull('payments.paid_at')
            ->whereNotNull('payment_items.discount_code')
            ->select('payment_items.discount_code', DB::raw('COUNT(DISTINCT payment_items.payment_id) as used_times'), DB::raw('SUM(payment_items.discount_amount) as total_discount'))
            ->groupBy('payment_items.discount_code')
            ->orderByDesc('used_times')
            ->take(5)
        )->get();
        $totalDiscountUses = $discountsUsage->sum('used_times');

        // Previous period (same length, immediately before current range)
        $periodDays = max(1, (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($periodDays - 1)->startOfDay();
        $previousFrom = $prevStart->toDateString();
        $previousTo = $prevEnd->toDateString();

        $prevPaidBase = $this->scopedPaymentsQuery()
            ->whereBetween('paid_at', [$prevStart, $prevEnd])
            ->where('status', 1)
            ->whereNotNull('paid_at');
        $prevTotalPayments = $this->scopedPaymentsQuery()->whereBetween('created_at', [$prevStart, $prevEnd]);
        $prevRegistrations = $canGlobalUsers
            ? (int) User::whereBetween('created_at', [$prevStart, $prevEnd])->count()
            : 0;
        $prevComments = (int) $this->scopedCommentsQuery()->whereBetween('created_at', [$prevStart, $prevEnd])->count();
        $prevRevenue = (int) (clone $prevPaidBase)->sum('amount');
        $prevPaidCount = (int) (clone $prevPaidBase)->count();
        $prevTotalCount = (int) $prevTotalPayments->count();
        $prevUniquePaying = (int) $this->scopedPaymentsQuery()
            ->whereBetween('paid_at', [$prevStart, $prevEnd])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->distinct('user_id')
            ->count('user_id');
        $prevConversion = $prevTotalCount > 0 ? round(($prevPaidCount / $prevTotalCount) * 100, 2) : 0;
        $prevAov = $prevPaidCount > 0 ? (int) floor($prevRevenue / $prevPaidCount) : 0;

        $platform = $this->buildPlatformStats($start, $end, $prevStart, $prevEnd, $labelDates, $groupBy, $scope);

        $comparison = array_merge([
            'revenue' => $this->compareMetric((int) $paymentsStats['total_amount'], $prevRevenue),
            'transactions' => $this->compareMetric((int) $paymentsStats['paid_payments'], $prevPaidCount),
            'registrations' => $this->compareMetric($registrationsInPeriod, $prevRegistrations),
            'comments' => $this->compareMetric($commentsInPeriod, $prevComments),
            'paying_users' => $this->compareMetric($uniquePayingUsers, $prevUniquePaying),
            'conversion_rate' => $this->compareMetric((int) round($paymentsStats['conversion_rate']), (int) round($prevConversion)),
            'aov' => $this->compareMetric((int) $paymentsStats['aov'], $prevAov),
        ], $platform['comparison']);

        $dailyCombined = $labelDates->map(function ($date, $idx) use ($dailyPaymentsAmount, $usersDaily, $commentsDaily, $platform) {
            return [
                'date' => $date,
                'sales' => (int) ($dailyPaymentsAmount[$idx] ?? 0),
                'users' => (int) ($usersDaily[$idx] ?? 0),
                'comments' => (int) ($commentsDaily[$idx] ?? 0),
                'views' => (int) ($platform['daily']['views'][$idx] ?? 0),
                'likes' => (int) ($platform['daily']['likes'][$idx] ?? 0),
            ];
        })->values();

        $prevDailyAmountRaw = $this->scopedPaymentsQuery()
            ->whereBetween('paid_at', [$prevStart, $prevEnd])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->select(DB::raw('DATE(paid_at) as grp'), DB::raw('sum(amount) as total'), DB::raw('count(*) as count'))
            ->groupBy('grp')
            ->orderBy('grp')
            ->get()
            ->keyBy('grp');

        $dailyComparison = $this->buildDailyComparison(
            $start->toDateString(),
            $end->toDateString(),
            $previousFrom,
            $previousTo,
            $dailyPaymentsAmount->all(),
            $dailyPaymentsCount->all(),
            $prevDailyAmountRaw
        );

        $typeMapTitle = [
            'course' => 'دوره',
            'path' => 'مسیر',
            'vip' => 'پلن VIP',
            'other' => 'سایر',
        ];

        $currentRevenueByType = $revenueByType->keyBy('type');
        $prevRevenueByTypeRaw = $scope->constrainJoinedPayments(
            PaymentItem::query()
            ->select('payment_items.payable_type', DB::raw('SUM(COALESCE(payment_items.final_price, payment_items.price)) as total'))
            ->join('payments', 'payments.id', '=', 'payment_items.payment_id')
            ->whereBetween('payments.paid_at', [$prevStart, $prevEnd])
            ->where('payments.status', 1)
            ->whereNotNull('payments.paid_at')
            ->groupBy('payment_items.payable_type')
        )->get()
            ->mapWithKeys(function ($row) {
                $type = 'other';
                if ($row->payable_type === Course::class) {
                    $type = 'course';
                }
                if ($row->payable_type === Path::class) {
                    $type = 'path';
                }
                if ($row->payable_type === Plan::class) {
                    $type = 'vip';
                }

                return [$type => (int) $row->total];
            });

        $revenueTypeComparison = collect(['course', 'path', 'vip', 'other'])->map(function ($type) use ($currentRevenueByType, $prevRevenueByTypeRaw, $typeMapTitle) {
            $currentRow = $currentRevenueByType->get($type);

            return [
                'type' => $type,
                'label' => $typeMapTitle[$type] ?? $type,
                'current' => (int) ($currentRow['total'] ?? 0),
                'previous' => (int) ($prevRevenueByTypeRaw[$type] ?? 0),
            ];
        })->values();

        $weekdayLabels = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
        $salesWeekdayRaw = $this->scopedPaymentsQuery()
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->select(DB::raw(SqlDialect::dayOfWeek('paid_at').' as weekday'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('weekday')
            ->get()
            ->keyBy('weekday');

        $salesByWeekday = collect(range(1, 7))->map(function ($day) use ($salesWeekdayRaw, $weekdayLabels) {
            $row = $salesWeekdayRaw[$day] ?? null;

            return [
                'weekday' => $day,
                'label' => $weekdayLabels[$day - 1] ?? (string) $day,
                'total' => (int) ($row->total ?? 0),
                'count' => (int) ($row->count ?? 0),
            ];
        })->values();

        $salesHourlyRaw = $this->scopedPaymentsQuery()
            ->whereBetween('paid_at', [$start, $end])
            ->where('status', 1)
            ->whereNotNull('paid_at')
            ->select(DB::raw(SqlDialect::hour('paid_at').' as hour'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        $salesByHour = collect(range(0, 23))->map(function ($hour) use ($salesHourlyRaw) {
            $row = $salesHourlyRaw[$hour] ?? null;

            return [
                'hour' => $hour,
                'label' => sprintf('%02d:00', $hour),
                'total' => (int) ($row->total ?? 0),
                'count' => (int) ($row->count ?? 0),
            ];
        })->values();

        $registrationsWeekdayRaw = $canGlobalUsers
            ? User::whereBetween('created_at', [$start, $end])
                ->select(DB::raw(SqlDialect::dayOfWeek('created_at').' as weekday'), DB::raw('COUNT(*) as count'))
                ->groupBy('weekday')
                ->pluck('count', 'weekday')
            : collect();

        $registrationsByWeekday = collect(range(1, 7))->map(function ($day) use ($registrationsWeekdayRaw, $weekdayLabels) {
            return [
                'weekday' => $day,
                'label' => $weekdayLabels[$day - 1] ?? (string) $day,
                'count' => (int) ($registrationsWeekdayRaw[$day] ?? 0),
            ];
        })->values();

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
            'capabilities' => $scope->dashboardCapabilities(),
            'settlement' => app(\App\Services\Settlement\SettlementSummary::class)->snapshot($request->user(), $start, $end),
            'comparison' => $comparison,
            'platform' => $platform['stats'],
            'analytics' => [
                'daily_combined' => $dailyCombined,
                'daily_comparison' => $dailyComparison,
                'engagement_daily_comparison' => $platform['daily_comparison'],
                'revenue_type_comparison' => $revenueTypeComparison,
                'sales_by_weekday' => $salesByWeekday,
                'sales_by_hour' => $salesByHour,
                'registrations_by_weekday' => $registrationsByWeekday,
                'views_by_type' => $platform['views_by_type'],
                'engagement_type_comparison' => $platform['type_comparison'],
                'views_by_hour' => $platform['views_by_hour'],
                'volume_mix' => [
                    'labels' => ['بازدید', 'لایک', 'کامنت', 'بوکمارک'],
                    'values' => [
                        (int) ($platform['stats']['engagement']['views'] ?? 0),
                        (int) ($platform['stats']['engagement']['likes'] ?? 0),
                        $commentsInPeriod,
                        (int) ($platform['stats']['engagement']['bookmarks'] ?? 0),
                    ],
                ],
                'activity_distribution' => $platform['activity_distribution'],
                'previous_period' => [
                    'date_from' => $previousFrom,
                    'date_to' => $previousTo,
                    'days' => $periodDays,
                ],
            ],
        ], 200);
    }

    private function buildPlatformStats(
        Carbon $start,
        Carbon $end,
        Carbon $prevStart,
        Carbon $prevEnd,
        $labelDates,
        string $groupBy,
        ContentScope $scope
    ): array {
        $groupExpr = fn (string $column) => SqlDialect::groupByPeriod($column, $groupBy);
        $canGlobalPlatform = $scope->canViewGlobalPlatformMetrics();
        $canGlobalUsers = $scope->canViewGlobalUserMetrics();

        $viewQuery = View::query()->whereBetween('created_at', [$start, $end]);
        $likeQuery = Like::query()->whereBetween('created_at', [$start, $end]);
        $bookmarkQuery = Bookmark::query()->whereBetween('created_at', [$start, $end]);
        if (! $canGlobalPlatform) {
            $viewQuery = $scope->applyToViewableEngagement($viewQuery);
            $likeQuery = $scope->applyToLikes($likeQuery);
            $bookmarkQuery = $scope->applyToBookmarks($bookmarkQuery);
        }

        $viewsTotal = (clone $viewQuery)->count();
        $likesTotal = (clone $likeQuery)->count();
        $likesPositive = (clone $likeQuery)->where('type', 'like')->count();
        $dislikesTotal = (clone $likeQuery)->where('type', 'dislike')->count();
        $bookmarksTotal = (clone $bookmarkQuery)->count();
        $guestViews = (clone $viewQuery)->whereNull('user_id')->count();
        $authViews = (clone $viewQuery)->whereNotNull('user_id')->count();
        $uniqueViewUsers = (clone $viewQuery)->whereNotNull('user_id')->distinct('user_id')->count('user_id');
        $uniqueLikeUsers = (clone $likeQuery)->distinct('user_id')->count('user_id');

        $prevViews = $canGlobalPlatform
            ? (int) View::whereBetween('created_at', [$prevStart, $prevEnd])->count()
            : (int) $scope->applyToViewableEngagement(View::query()->whereBetween('created_at', [$prevStart, $prevEnd]))->count();
        $prevLikes = $canGlobalPlatform
            ? (int) Like::whereBetween('created_at', [$prevStart, $prevEnd])->count()
            : (int) $scope->applyToLikes(Like::query()->whereBetween('created_at', [$prevStart, $prevEnd]))->count();
        $prevBookmarks = $canGlobalPlatform
            ? (int) Bookmark::whereBetween('created_at', [$prevStart, $prevEnd])->count()
            : (int) $scope->applyToBookmarks(Bookmark::query()->whereBetween('created_at', [$prevStart, $prevEnd]))->count();

        $loginsCount = $canGlobalUsers ? (int) UserLogin::whereBetween('logged_in_at', [$start, $end])->count() : 0;
        $activeUsers = $canGlobalUsers ? (int) UserLogin::whereBetween('logged_in_at', [$start, $end])->distinct('user_id')->count('user_id') : 0;
        $videoViewsCount = (int) $scope->applyToVideoViews(VideoView::query()->whereBetween('updated_at', [$start, $end]))->count();
        $questionsCount = $canGlobalPlatform ? (int) Question::whereBetween('created_at', [$start, $end])->count() : 0;
        $answersCount = $canGlobalPlatform ? (int) Answer::whereBetween('created_at', [$start, $end])->count() : 0;
        $articlesCount = $canGlobalPlatform
            ? (int) Article::whereBetween('created_at', [$start, $end])->count()
            : (int) $scope->applyToArticles(Article::query()->whereBetween('created_at', [$start, $end]))->count();
        $certificatesCount = (int) $scope->applyToCertificates(Certificate::query()->whereBetween('issued_at', [$start, $end]))->count();

        $prevLogins = $canGlobalUsers ? (int) UserLogin::whereBetween('logged_in_at', [$prevStart, $prevEnd])->count() : 0;
        $prevActiveUsers = $canGlobalUsers ? (int) UserLogin::whereBetween('logged_in_at', [$prevStart, $prevEnd])->distinct('user_id')->count('user_id') : 0;
        $prevVideoViews = (int) $scope->applyToVideoViews(VideoView::query()->whereBetween('updated_at', [$prevStart, $prevEnd]))->count();
        $prevQuestions = $canGlobalPlatform ? (int) Question::whereBetween('created_at', [$prevStart, $prevEnd])->count() : 0;
        $prevAnswers = $canGlobalPlatform ? (int) Answer::whereBetween('created_at', [$prevStart, $prevEnd])->count() : 0;
        $prevArticles = $canGlobalPlatform
            ? (int) Article::whereBetween('created_at', [$prevStart, $prevEnd])->count()
            : (int) $scope->applyToArticles(Article::query()->whereBetween('created_at', [$prevStart, $prevEnd]))->count();

        $viewsDailyRaw = (clone $viewQuery)
            ->select(DB::raw($groupExpr('created_at').' as grp'), DB::raw('COUNT(*) as count'))
            ->groupBy('grp')
            ->pluck('count', 'grp');
        $likesDailyRaw = (clone $likeQuery)
            ->select(DB::raw($groupExpr('created_at').' as grp'), DB::raw('COUNT(*) as count'))
            ->groupBy('grp')
            ->pluck('count', 'grp');

        $viewsDaily = $labelDates->map(fn ($date) => (int) ($viewsDailyRaw[$date] ?? 0))->values()->all();
        $likesDaily = $labelDates->map(fn ($date) => (int) ($likesDailyRaw[$date] ?? 0))->values()->all();

        $prevViewsDailyRaw = $scope->applyToViewableEngagement(
            View::query()->whereBetween('created_at', [$prevStart, $prevEnd])
        )
            ->select(DB::raw('DATE(created_at) as grp'), DB::raw('COUNT(*) as count'))
            ->groupBy('grp')
            ->pluck('count', 'grp');
        $prevLikesDailyRaw = ($canGlobalPlatform ? Like::query() : $scope->applyToLikes(Like::query()))
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->select(DB::raw('DATE(created_at) as grp'), DB::raw('COUNT(*) as count'))
            ->groupBy('grp')
            ->pluck('count', 'grp');

        $prevPeriod = collect(CarbonPeriod::create($prevStart->toDateString(), $prevEnd->toDateString()))->values();
        $previousViews = [];
        $previousLikes = [];
        $idx = 0;
        foreach ($labelDates as $date) {
            $prevDate = $prevPeriod[$idx] ?? null;
            $prevKey = $prevDate ? $prevDate->toDateString() : null;
            $previousViews[] = $prevKey ? (int) ($prevViewsDailyRaw[$prevKey] ?? 0) : 0;
            $previousLikes[] = $prevKey ? (int) ($prevLikesDailyRaw[$prevKey] ?? 0) : 0;
            $idx++;
        }

        $engagementDailyComparison = [
            'labels' => $labelDates->all(),
            'current_views' => $viewsDaily,
            'previous_views' => $previousViews,
            'current_likes' => $likesDaily,
            'previous_likes' => $previousLikes,
        ];

        $typeMap = [
            Course::class => 'دوره',
            Episode::class => 'قسمت',
            Question::class => 'پرسش',
            Article::class => 'مقاله',
            Path::class => 'مسیر',
            User::class => 'پروفایل',
        ];

        $viewsByType = (clone $viewQuery)
            ->select('viewable_type', DB::raw('COUNT(*) as count'))
            ->groupBy('viewable_type')
            ->orderByDesc('count')
            ->get()
            ->map(function ($row) use ($typeMap) {
                $label = $typeMap[$row->viewable_type] ?? class_basename($row->viewable_type);

                return [
                    'type' => class_basename($row->viewable_type),
                    'label' => $label,
                    'count' => (int) $row->count,
                ];
            })
            ->values();

        $periodComments = (int) $scope->applyToComments(Comment::query()->whereBetween('created_at', [$start, $end]))->count();

        $typeComparison = collect([Course::class, Episode::class, Question::class, Article::class])->map(function ($class) use ($viewQuery, $likeQuery, $typeMap, $start, $end, $scope) {
            $short = class_basename($class);
            $views = (int) (clone $viewQuery)->where('viewable_type', $class)->count();
            $likes = (int) (clone $likeQuery)->where('likeable_type', $class)->count();
            $comments = (int) $scope->applyToComments(
                Comment::query()
                    ->where('commentable_type', $class)
                    ->whereBetween('created_at', [$start, $end])
            )->count();

            return [
                'type' => $short,
                'label' => $typeMap[$class] ?? $short,
                'views' => $views,
                'likes' => $likes,
                'comments' => $comments,
            ];
        })->values();

        $viewsHourlyRaw = (clone $viewQuery)
            ->select(DB::raw(SqlDialect::hour('created_at').' as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->pluck('count', 'hour');
        $viewsByHour = collect(range(0, 23))->map(fn ($hour) => [
            'hour' => $hour,
            'label' => sprintf('%02d:00', $hour),
            'count' => (int) ($viewsHourlyRaw[$hour] ?? 0),
        ])->values();

        $topViewed = (clone $viewQuery)
            ->select('viewable_type', 'viewable_id', DB::raw('COUNT(*) as views_count'))
            ->groupBy('viewable_type', 'viewable_id')
            ->orderByDesc('views_count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => $this->formatTopViewedItem($row))
            ->filter()
            ->values();

        $likesPer100Views = $viewsTotal > 0 ? round(($likesPositive / $viewsTotal) * 100, 2) : 0;
        $commentsPer100Views = $viewsTotal > 0 ? round(($periodComments / $viewsTotal) * 100, 2) : 0;

        $vipUsers = $canGlobalUsers ? (int) User::whereHas('plans', fn ($q) => $q->where('plan_user.expired_at', '>', now()))->count() : 0;
        $activeAccounts = $canGlobalUsers ? (int) User::where('active', true)->count() : 0;
        $inactiveAccounts = $canGlobalUsers ? (int) User::where('active', false)->count() : 0;

        $activityDistribution = [
            'labels' => ['بازدید', 'لایک', 'کامنت', 'بوکمارک', 'لاگین', 'تماشای ویدیو', 'سوال', 'پاسخ', 'مقاله'],
            'values' => [
                $viewsTotal,
                $likesTotal,
                $periodComments,
                $bookmarksTotal,
                $loginsCount,
                $videoViewsCount,
                $questionsCount,
                $answersCount,
                $articlesCount,
            ],
            'colors' => ['#3b82f6', '#f43f5e', '#06b6d4', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#6366f1', '#eab308'],
        ];

        return [
            'comparison' => [
                'views' => $this->compareMetric($viewsTotal, $prevViews),
                'likes' => $this->compareMetric($likesTotal, $prevLikes),
                'bookmarks' => $this->compareMetric($bookmarksTotal, $prevBookmarks),
                'video_views' => $this->compareMetric($videoViewsCount, $prevVideoViews),
                'active_users' => $this->compareMetric($activeUsers, $prevActiveUsers),
                'logins' => $this->compareMetric($loginsCount, $prevLogins),
                'questions' => $this->compareMetric($questionsCount, $prevQuestions),
                'answers' => $this->compareMetric($answersCount, $prevAnswers),
                'articles' => $this->compareMetric($articlesCount, $prevArticles),
            ],
            'daily' => [
                'views' => $viewsDaily,
                'likes' => $likesDaily,
            ],
            'daily_comparison' => $engagementDailyComparison,
            'views_by_type' => $viewsByType,
            'type_comparison' => $typeComparison,
            'views_by_hour' => $viewsByHour,
            'activity_distribution' => $activityDistribution,
            'stats' => [
                'content' => [
                    'courses' => (int) $scope->applyToCourses(Course::query())->count(),
                    'paths' => $canGlobalPlatform ? Path::count() : 0,
                    'episodes' => $canGlobalPlatform
                        ? Episode::count()
                        : (int) Episode::query()->whereHas('section.course', fn ($q) => $q->where('teacher_id', auth()->id()))->count(),
                    'questions' => $canGlobalPlatform ? Question::count() : 0,
                    'articles' => (int) $scope->applyToArticles(Article::query())->count(),
                    'plans' => $canGlobalPlatform ? Plan::count() : 0,
                    'certificates' => (int) $scope->applyToCertificates(Certificate::query())->count(),
                ],
                'engagement' => [
                    'views' => $viewsTotal,
                    'views_today' => (int) $scope->applyToViewableEngagement(View::query()->whereDate('created_at', today()))->count(),
                    'guest_views' => $guestViews,
                    'authenticated_views' => $authViews,
                    'likes' => $likesTotal,
                    'likes_positive' => $likesPositive,
                    'dislikes' => $dislikesTotal,
                    'bookmarks' => $bookmarksTotal,
                    'unique_view_users' => $uniqueViewUsers,
                    'unique_like_users' => $uniqueLikeUsers,
                    'interactions' => $likesTotal + $periodComments,
                ],
                'activity' => [
                    'active_users' => $activeUsers,
                    'logins' => $loginsCount,
                    'video_views' => $videoViewsCount,
                    'questions' => $questionsCount,
                    'answers' => $answersCount,
                    'articles' => $articlesCount,
                    'certificates' => $certificatesCount,
                    'today' => [
                        'logins' => (int) UserLogin::whereDate('logged_in_at', today())->count(),
                        'video_views' => (int) VideoView::whereDate('updated_at', today())->count(),
                        'views' => $canGlobalPlatform
                            ? (int) View::whereDate('created_at', today())->count()
                            : (int) $scope->applyToViewableEngagement(View::query()->whereDate('created_at', today()))->count(),
                        'likes' => $canGlobalPlatform
                            ? (int) Like::whereDate('created_at', today())->count()
                            : (int) $scope->applyToLikes(Like::query()->whereDate('created_at', today()))->count(),
                    ],
                ],
                'users' => [
                    'vip' => $vipUsers,
                    'active_accounts' => $activeAccounts,
                    'inactive_accounts' => $inactiveAccounts,
                ],
                'ratios' => [
                    'likes_per_100_views' => $likesPer100Views,
                    'comments_per_100_views' => $commentsPer100Views,
                ],
                'moderation' => [
                    'unapproved_comments' => (int) $scope->applyToComments(Comment::query()->where('approved', 0))->count(),
                ],
                'top_viewed' => $topViewed,
            ],
        ];
    }

    private function formatTopViewedItem($row): ?array
    {
        $type = $row->viewable_type;
        $id = $row->viewable_id;
        $title = null;
        $typeLabel = class_basename($type);

        if ($type === Course::class) {
            $title = Course::find($id)?->title;
            $typeLabel = 'دوره';
        } elseif ($type === Episode::class) {
            $title = Episode::find($id)?->title;
            $typeLabel = 'قسمت';
        } elseif ($type === Question::class) {
            $title = Question::find($id)?->subject;
            $typeLabel = 'پرسش';
        } elseif ($type === Article::class) {
            $title = Article::find($id)?->title;
            $typeLabel = 'مقاله';
        } elseif ($type === Path::class) {
            $title = Path::find($id)?->title;
            $typeLabel = 'مسیر';
        } elseif ($type === User::class) {
            $user = User::find($id);
            $title = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : null;
            if ($title === '' && $user) {
                $title = '@' . $user->username;
            }
            $typeLabel = 'پروفایل';
        }

        if (!$title) {
            return null;
        }

        return [
            'title' => $title,
            'content_type' => class_basename($type),
            'content_type_label' => $typeLabel,
            'views_count' => (int) $row->views_count,
        ];
    }

    private function compareMetric(int $current, int $previous): array
    {
        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $current - $previous,
            'change_percent' => $previous > 0
                ? round((($current - $previous) / $previous) * 100, 1)
                : ($current > 0 ? 100.0 : 0.0),
        ];
    }

    private function buildDailyComparison(
        string $dateFrom,
        string $dateTo,
        string $previousFrom,
        string $previousTo,
        array $currentAmounts,
        array $currentCounts,
        $previousRows
    ): array {
        $labels = [];
        $currentAmount = [];
        $previousAmount = [];
        $currentCount = [];
        $previousCount = [];

        $prevPeriod = collect(CarbonPeriod::create($previousFrom, $previousTo))->values();
        $idx = 0;
        $amountList = array_values($currentAmounts);
        $countList = array_values($currentCounts);

        foreach (CarbonPeriod::create($dateFrom, $dateTo) as $date) {
            $key = $date->toDateString();
            $labels[] = $key;
            $currentAmount[] = (int) ($amountList[$idx] ?? 0);
            $currentCount[] = (int) ($countList[$idx] ?? 0);

            $prevDate = $prevPeriod[$idx] ?? null;
            if ($prevDate) {
                $prevKey = $prevDate->toDateString();
                $row = $previousRows[$prevKey] ?? null;
                $previousAmount[] = (int) ($row->total ?? 0);
                $previousCount[] = (int) ($row->count ?? 0);
            } else {
                $previousAmount[] = 0;
                $previousCount[] = 0;
            }

            $idx++;
        }

        return [
            'labels' => $labels,
            'current_amount' => $currentAmount,
            'previous_amount' => $previousAmount,
            'current_count' => $currentCount,
            'previous_count' => $previousCount,
        ];
    }

    public function unapprovedCommentsCount()
    {
        $count = $this->contentScope()->applyToComments(
            Comment::where('approved', 0)
        )->count();
        
        return response()->json([
            'message' => 'Success',
            'count' => $count,
        ]);
    }
}


