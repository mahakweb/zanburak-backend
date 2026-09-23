<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AppliesContentScope;
use App\Models\Payment;
use App\Models\PaymentItem;
use App\Models\Course;
use App\Models\Path;
use App\Models\Plan;
use App\Models\User;
use App\Models\Category;
use App\Models\Level;
use App\Models\Discount;
use App\Support\SqlDialect;
use App\Services\Security\ContentScope;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    use AppliesContentScope;

    /**
     * Get sales report with filters and pagination
     */
    public function salesReport(Request $request)
    {
        // Only get paid payments
        $query = $this->contentScope()->applyToPayments(
            Payment::with([
            'user:id,first_name,last_name,username,email,profile_pic',
            'items.payable',
        ])->where('status', 1)
          ->whereNotNull('paid_at')
        );

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('paid_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('paid_at', '<=', $request->date_to);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->forReportedMethod($request->payment_method);
        }

        // Filter by driver
        if ($request->filled('driver')) {
            $query->where('driver', $request->driver);
        }

        // Filter by amount range
        if ($request->filled('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }

        if ($request->filled('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        // Filter by payable type (course, path, plan)
        if ($request->filled('payable_type')) {
            $query->whereHas('items', function($q) use ($request) {
                $q->where('payable_type', 'like', '%' . $request->payable_type . '%');
            });
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                  ->orWhere('reference_id', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Apply sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('paid_at', 'asc');
                break;
            case 'amount_high':
                $query->orderBy('amount', 'desc');
                break;
            case 'amount_low':
                $query->orderBy('amount', 'asc');
                break;
            case 'newest':
            default:
                $query->orderBy('paid_at', 'desc');
                break;
        }

        $perPage = $request->input('perPage', 20);
        $payments = $query->paginate($perPage);

        $data = collect($payments->items())->map(function ($payment) {
            return [
                'id' => $payment->id,
                'uuid' => $payment->uuid,
                'reference_id' => $payment->reference_id,
                'tracking_number' => $payment->tracking_number,
                'original_amount' => $payment->amount + ($payment->discount_amount ?? 0),
                'amount' => $payment->amount + ($payment->discount_amount ?? 0),
                'discount_amount' => $payment->discount_amount ?? 0,
                'discount_code' => $payment->discount_code,
                'final_amount' => $payment->amount,
                'driver' => $payment->driver,
                'payment_method' => $payment->payment_method,
                'wallet_paid_amount' => $payment->wallet_paid_amount,
                'gateway_paid_amount' => $payment->gateway_paid_amount,
                'paid_at' => $payment->paid_at,
                'created_at' => $payment->created_at,
                
                'user' => $payment->user ? [
                    'id' => $payment->user->id,
                    'first_name' => $payment->user->first_name,
                    'last_name' => $payment->user->last_name,
                    'username' => $payment->user->username,
                    'email' => $payment->user->email,
                    'profile_pic' => $payment->user->profile_pic,
                ] : null,

                'items' => $payment->items->map(function ($item) {
                    $base = [
                        'id' => $item->id,
                        'payable_type' => class_basename($item->payable_type),
                        'payable_id' => $item->payable_id,
                        'price' => $item->price,
                        'discount_amount' => $item->discount_amount,
                        'discount_code' => $item->discount_code,
                        'final_price' => max(0, $item->final_price ?? ($item->price - ($item->discount_amount ?? 0))),
                    ];

                    if ($item->relationLoaded('payable') && $item->payable) {
                        $payable = $item->payable;

                        if ($payable instanceof \App\Models\Course) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'slug' => $payable->slug,
                                'poster' => $payable->poster,
                                'price' => $payable->price,
                            ];
                        } elseif ($payable instanceof \App\Models\Path) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'slug' => $payable->slug,
                                'poster' => $payable->poster,
                            ];
                        } elseif ($payable instanceof \App\Models\Plan) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'price' => $payable->price,
                            ];
                        }
                    }

                    return $base;
                }),
            ];
        });

        return response()->json([
            'message' => 'Success',
            'sales' => $data,
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ]
        ], 200);
    }

    /**
     * Get sales statistics
     */
    public function stats(Request $request)
    {
        [$dateFrom, $dateTo, $periodDays, $previousFrom, $previousTo] = $this->resolveDateRange($request);

        $query = $this->scopedPaidPaymentsQuery();
        $this->applyPaymentFilters($query, $request);
        $this->applyDateRangeOnPaidAt($query, $dateFrom, $dateTo);

        $baseQuery = clone $query;

        $totalOriginalSales = (int) (clone $baseQuery)->sum(DB::raw('amount + COALESCE(discount_amount, 0)'));
        $totalDiscount = (int) (clone $baseQuery)->sum('discount_amount');
        $netSales = (int) (clone $baseQuery)->sum('amount');
        $totalTransactions = (clone $baseQuery)->count();
        $uniqueBuyers = (clone $baseQuery)->distinct('user_id')->count('user_id');
        $averageTransaction = $totalTransactions > 0 ? $netSales / $totalTransactions : 0;

        $previousQuery = $this->scopedPaidPaymentsQuery();
        $this->applyPaymentFilters($previousQuery, $request);
        $this->applyDateRangeOnPaidAt($previousQuery, $previousFrom, $previousTo);

        $prevNetSales = (int) (clone $previousQuery)->sum('amount');
        $prevTransactions = (clone $previousQuery)->count();
        $prevAverage = $prevTransactions > 0 ? $prevNetSales / $prevTransactions : 0;
        $prevDiscount = (int) (clone $previousQuery)->sum('discount_amount');
        $prevUniqueBuyers = (clone $previousQuery)->distinct('user_id')->count('user_id');

        $salesByMethod = (clone $query)
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($item) => [
                'method' => $item->payment_method,
                'total' => (int) $item->total,
                'count' => (int) $item->count,
            ]);

        $salesByDriver = (clone $query)
            ->select('driver', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->whereNotNull('driver')
            ->groupBy('driver')
            ->get()
            ->map(fn ($item) => [
                'driver' => $item->driver,
                'total' => (int) $item->total,
                'count' => (int) $item->count,
            ]);

        $salesByType = PaymentItem::whereHas('payment', function ($q) use ($request, $dateFrom, $dateTo) {
                $q->where('status', 1)->whereNotNull('paid_at');
                $this->contentScope()->applyToPayments($q);
                $this->applyPaymentFilters($q, $request);
                $this->applyDateRangeOnPaidAt($q, $dateFrom, $dateTo);
            })
            ->select('payable_type', DB::raw('SUM(final_price) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payable_type')
            ->get()
            ->map(fn ($item) => [
                'type' => class_basename($item->payable_type),
                'total' => (int) $item->total,
                'count' => (int) $item->count,
            ]);

        $dailyRaw = (clone $query)
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->pluck('count', 'date')
            ->toArray();
        $dailyAmountRaw = (clone $query)
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->pluck('total', 'date')
            ->toArray();

        $dailySales = $this->buildDailySalesSeries($dateFrom, $dateTo, $dailyAmountRaw, $dailyRaw);

        $prevDailyAmountRaw = (clone $previousQuery)
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->get()
            ->keyBy('date');

        $dailyComparison = $this->buildDailyComparison($dateFrom, $dateTo, $previousFrom, $previousTo, $dailyAmountRaw, $dailyRaw, $prevDailyAmountRaw);

        $hourly = $this->buildHourlySales($query);
        $monthly = $this->buildMonthlySales($query, $dateFrom, $dateTo, $periodDays);
        $heatmap = $this->buildSalesHeatmap($query);
        $insights = $this->buildSalesInsights($query, $netSales, $uniqueBuyers, $totalTransactions, $totalOriginalSales, $totalDiscount);

        $comparisonStats = [
            'net_sales' => $this->compareMetric($netSales, $prevNetSales),
            'transactions' => $this->compareMetric($totalTransactions, $prevTransactions),
            'average_transaction' => $this->compareMetric((int) round($averageTransaction), (int) round($prevAverage)),
            'discount' => $this->compareMetric($totalDiscount, $prevDiscount),
            'unique_buyers' => $this->compareMetric($uniqueBuyers, $prevUniqueBuyers),
        ];

        return response()->json([
            'message' => 'Success',
            'period' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'stats' => [
                'total_sales' => $totalOriginalSales,
                'total_discount' => $totalDiscount,
                'net_sales' => $netSales,
                'total_transactions' => $totalTransactions,
                'unique_buyers' => $uniqueBuyers,
                'average_transaction' => (int) round($averageTransaction),
                'discount_rate' => $totalOriginalSales > 0 ? round(($totalDiscount / $totalOriginalSales) * 100, 1) : 0,
                'sales_by_method' => $salesByMethod,
                'sales_by_driver' => $salesByDriver,
                'sales_by_type' => $salesByType,
                'daily_sales' => $dailySales,
                'daily_comparison' => $dailyComparison,
                'hourly' => $hourly,
                'monthly' => $monthly,
                'heatmap' => $heatmap,
                'insights' => $insights,
                'comparison' => $comparisonStats,
                'today' => [
                    'net_sales' => (int) $this->scopedPaidPaymentsQuery()->whereDate('paid_at', today())->sum('amount'),
                    'transactions' => $this->scopedPaidPaymentsQuery()->whereDate('paid_at', today())->count(),
                ],
            ],
        ], 200);
    }

    /**
     * Export sales report
     */
    public function export(Request $request)
    {
        // Similar to salesReport but without pagination
        $query = $this->contentScope()->applyToPayments(
            Payment::with([
            'user:id,first_name,last_name,username,email',
            'items.payable',
        ])->where('status', 1)
          ->whereNotNull('paid_at')
        );

        // Apply same filters as salesReport
        if ($request->filled('date_from')) {
            $query->whereDate('paid_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('paid_at', '<=', $request->date_to);
        }

        if ($request->filled('driver')) {
            $query->where('driver', $request->driver);
        }

        if ($request->filled('payment_method')) {
            $query->forReportedMethod($request->payment_method);
        }

        $sales = $query->orderBy('paid_at', 'desc')->get();

        $data = $sales->map(function ($payment) {
            return [
                'uuid' => $payment->uuid,
                'reference_id' => $payment->reference_id,
                'user_name' => $payment->user ? $payment->user->first_name . ' ' . $payment->user->last_name : 'نامشخص',
                'user_email' => $payment->user->email ?? 'نامشخص',
                'original_amount' => $payment->amount + ($payment->discount_amount ?? 0),
                'amount' => $payment->amount + ($payment->discount_amount ?? 0),
                'discount_amount' => $payment->discount_amount ?? 0,
                'final_amount' => $payment->amount,
                'driver' => $payment->driver ?? 'نامشخص',
                'payment_method' => $payment->payment_method,
                'wallet_paid_amount' => $payment->wallet_paid_amount,
                'gateway_paid_amount' => $payment->gateway_paid_amount,
                'paid_at' => $payment->paid_at->format('Y-m-d H:i:s'),
                'items_count' => $payment->items->count(),
                'items' => $payment->items->map(function($item) {
                    $payable = $item->payable;
                    if ($payable) {
                        if ($payable instanceof \App\Models\Course) {
                            return $payable->title;
                        } elseif ($payable instanceof \App\Models\Path) {
                            return $payable->title;
                        } elseif ($payable instanceof \App\Models\Plan) {
                            return $payable->title;
                        }
                    }
                    return class_basename($item->payable_type) . ' (ID: ' . $item->payable_id . ')';
                })->implode(', '),
            ];
        });

        return response()->json([
            'message' => 'Success',
            'data' => $data
        ], 200);
    }

    /**
     * Get advanced analytics
     */
    public function analytics(Request $request)
    {
        $baseQuery = $this->scopedPaidPaymentsQuery();

        // Apply filters
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('paid_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $baseQuery->whereDate('paid_at', '<=', $request->date_to);
        }

        if ($request->filled('payment_method')) {
            $baseQuery->forReportedMethod($request->payment_method);
        }

        if ($request->filled('driver')) {
            $baseQuery->where('driver', $request->driver);
        }

        $paymentIds = (clone $baseQuery)->pluck('id');

        // Top selling courses
        $topCourses = PaymentItem::whereIn('payment_id', $paymentIds)
            ->where('payable_type', Course::class)
            ->select('payable_id', 
                DB::raw('SUM(GREATEST(0, COALESCE(final_price, price - COALESCE(discount_amount, 0)))) as total_revenue'),
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('SUM(COALESCE(discount_amount, 0)) as total_discount')
            )
            ->groupBy('payable_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $course = Course::with('teacher:id,first_name,last_name')->find($item->payable_id);
                return [
                    'id' => $item->payable_id,
                    'title' => $course->title ?? 'نامشخص',
                    'slug' => $course->slug ?? null,
                    'poster' => $course->poster ?? null,
                    'price' => $course->price ?? 0,
                    'total_revenue' => max(0, $item->total_revenue ?? 0),
                    'sales_count' => $item->sales_count,
                    'total_discount' => $item->total_discount ?? 0,
                    'teacher' => $course && $course->teacher ? [
                        'id' => $course->teacher->id,
                        'name' => $course->teacher->first_name . ' ' . $course->teacher->last_name
                    ] : null
                ];
            });

        // Top selling paths
        $topPaths = PaymentItem::whereIn('payment_id', $paymentIds)
            ->where('payable_type', Path::class)
            ->select('payable_id',
                DB::raw('SUM(GREATEST(0, COALESCE(final_price, price - COALESCE(discount_amount, 0)))) as total_revenue'),
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('SUM(COALESCE(discount_amount, 0)) as total_discount')
            )
            ->groupBy('payable_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $path = Path::find($item->payable_id);
                return [
                    'id' => $item->payable_id,
                    'title' => $path->title ?? 'نامشخص',
                    'slug' => $path->slug ?? null,
                    'poster' => $path->poster ?? null,
                    'total_revenue' => max(0, $item->total_revenue ?? 0),
                    'sales_count' => $item->sales_count,
                    'total_discount' => $item->total_discount ?? 0,
                ];
            });

        // Top selling plans
        $topPlans = PaymentItem::whereIn('payment_id', $paymentIds)
            ->where('payable_type', Plan::class)
            ->select('payable_id',
                DB::raw('SUM(GREATEST(0, COALESCE(final_price, price - COALESCE(discount_amount, 0)))) as total_revenue'),
                DB::raw('COUNT(*) as sales_count'),
                DB::raw('SUM(COALESCE(discount_amount, 0)) as total_discount')
            )
            ->groupBy('payable_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $plan = Plan::find($item->payable_id);
                return [
                    'id' => $item->payable_id,
                    'title' => $plan->title ?? 'نامشخص',
                    'price' => $plan->price ?? 0,
                    'total_revenue' => max(0, $item->total_revenue ?? 0),
                    'sales_count' => $item->sales_count,
                    'total_discount' => $item->total_discount ?? 0,
                ];
            });

        // Most active users (by purchase count and amount)
        $activeUsers = (clone $baseQuery)
            ->select('user_id',
                DB::raw('SUM(amount) as total_spent'),
                DB::raw('COUNT(*) as purchase_count')
            )
            ->groupBy('user_id')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $user = User::find($item->user_id);
                return [
                    'id' => $item->user_id,
                    'name' => $user ? $user->first_name . ' ' . $user->last_name : 'نامشخص',
                    'username' => $user->username ?? null,
                    'email' => $user->email ?? null,
                    'profile_pic' => $user->profile_pic ?? null,
                    'total_spent' => $item->total_spent,
                    'purchase_count' => $item->purchase_count,
                ];
            });

        // Top selling categories
        $topCategories = PaymentItem::whereIn('payment_id', $paymentIds)
            ->where('payable_type', Course::class)
            ->join('courses', 'payment_items.payable_id', '=', 'courses.id')
            ->join('category_course', 'courses.id', '=', 'category_course.course_id')
            ->join('categories', 'category_course.category_id', '=', 'categories.id')
            ->select('categories.id',
                'categories.title',
                'categories.slug',
                DB::raw('SUM(GREATEST(0, COALESCE(payment_items.final_price, payment_items.price - COALESCE(payment_items.discount_amount, 0)))) as total_revenue'),
                DB::raw('COUNT(DISTINCT payment_items.payable_id) as courses_count'),
                DB::raw('COUNT(payment_items.id) as sales_count')
            )
            ->groupBy('categories.id', 'categories.title', 'categories.slug')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'total_revenue' => max(0, $item->total_revenue ?? 0),
                    'courses_count' => $item->courses_count,
                    'sales_count' => $item->sales_count,
                ];
            });

        // Top selling levels
        $topLevels = PaymentItem::whereIn('payment_id', $paymentIds)
            ->where('payable_type', Course::class)
            ->join('courses', 'payment_items.payable_id', '=', 'courses.id')
            ->join('levels', 'courses.level_id', '=', 'levels.id')
            ->select('levels.id',
                'levels.title',
                'levels.slug',
                DB::raw('SUM(GREATEST(0, COALESCE(payment_items.final_price, payment_items.price - COALESCE(payment_items.discount_amount, 0)))) as total_revenue'),
                DB::raw('COUNT(DISTINCT payment_items.payable_id) as courses_count'),
                DB::raw('COUNT(payment_items.id) as sales_count')
            )
            ->groupBy('levels.id', 'levels.title', 'levels.slug')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'slug' => $item->slug,
                    'total_revenue' => max(0, $item->total_revenue ?? 0),
                    'courses_count' => $item->courses_count,
                    'sales_count' => $item->sales_count,
                ];
            });

        // Top teachers (by revenue from their courses)
        $topTeachers = PaymentItem::whereIn('payment_id', $paymentIds)
            ->where('payable_type', Course::class)
            ->join('courses', 'payment_items.payable_id', '=', 'courses.id')
            ->join('users', 'courses.teacher_id', '=', 'users.id')
            ->select('users.id',
                DB::raw(SqlDialect::concatWs(' ', ['users.first_name', 'users.last_name']).' as name'),
                'users.username',
                'users.email',
                DB::raw('SUM(GREATEST(0, COALESCE(payment_items.final_price, payment_items.price - COALESCE(payment_items.discount_amount, 0)))) as total_revenue'),
                DB::raw('COUNT(DISTINCT payment_items.payable_id) as courses_count'),
                DB::raw('COUNT(payment_items.id) as sales_count')
            )
            ->whereNotNull('courses.teacher_id')
            ->when($this->contentScope()->viewScope('payments') === ContentScope::OWN, function ($q) {
                $q->where('users.id', auth()->id());
            })
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.username', 'users.email')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'username' => $item->username,
                    'email' => $item->email,
                    'total_revenue' => max(0, $item->total_revenue ?? 0),
                    'courses_count' => $item->courses_count,
                    'sales_count' => $item->sales_count,
                ];
            });

        // Most used discount codes
        $topDiscountCodes = (clone $baseQuery)
            ->whereNotNull('discount_code')
            ->select('discount_code',
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('COUNT(*) as usage_count'),
                DB::raw('SUM(amount + discount_amount) as total_original_amount')
            )
            ->groupBy('discount_code')
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($item) {
                $discount = Discount::where('code', $item->discount_code)->first();
                return [
                    'code' => $item->discount_code,
                    'total_discount' => $item->total_discount,
                    'usage_count' => $item->usage_count,
                    'total_original_amount' => $item->total_original_amount,
                    'discount' => $discount ? [
                        'id' => $discount->id,
                        'title' => $discount->title ?? null,
                        'type' => $discount->type ?? null,
                        'value' => $discount->value ?? null,
                    ] : null
                ];
            });

        // Sales by category (for chart)
        $salesByCategory = PaymentItem::whereIn('payment_id', $paymentIds)
            ->where('payable_type', Course::class)
            ->join('courses', 'payment_items.payable_id', '=', 'courses.id')
            ->join('category_course', 'courses.id', '=', 'category_course.course_id')
            ->join('categories', 'category_course.category_id', '=', 'categories.id')
            ->select('categories.id',
                'categories.title',
                DB::raw('SUM(GREATEST(0, COALESCE(payment_items.final_price, payment_items.price - COALESCE(payment_items.discount_amount, 0)))) as total')
            )
            ->groupBy('categories.id', 'categories.title')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'total' => max(0, $item->total ?? 0),
                ];
            });

        // Sales by level (for chart)
        $salesByLevel = PaymentItem::whereIn('payment_id', $paymentIds)
            ->where('payable_type', Course::class)
            ->join('courses', 'payment_items.payable_id', '=', 'courses.id')
            ->join('levels', 'courses.level_id', '=', 'levels.id')
            ->select('levels.id',
                'levels.title',
                DB::raw('SUM(GREATEST(0, COALESCE(payment_items.final_price, payment_items.price - COALESCE(payment_items.discount_amount, 0)))) as total')
            )
            ->groupBy('levels.id', 'levels.title')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'total' => max(0, $item->total ?? 0),
                ];
            });

        return response()->json([
            'message' => 'Success',
            'analytics' => [
                'top_courses' => $topCourses,
                'top_paths' => $topPaths,
                'top_plans' => $topPlans,
                'active_users' => $activeUsers,
                'top_categories' => $topCategories,
                'top_levels' => $topLevels,
                'top_teachers' => $topTeachers,
                'top_discount_codes' => $topDiscountCodes,
                'sales_by_category' => $salesByCategory,
                'sales_by_level' => $salesByLevel,
            ]
        ], 200);
    }

    private function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $periodDays = max(1, (int) Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1);
        $previousFrom = Carbon::parse($dateFrom)->subDays($periodDays)->toDateString();
        $previousTo = Carbon::parse($dateFrom)->subDay()->toDateString();

        return [$dateFrom, $dateTo, $periodDays, $previousFrom, $previousTo];
    }

    private function applyDateRangeOnPaidAt(Builder $query, string $dateFrom, string $dateTo): void
    {
        $query->whereDate('paid_at', '>=', $dateFrom)
            ->whereDate('paid_at', '<=', $dateTo);
    }

    private function applyPaymentFilters(Builder $query, Request $request): void
    {
        if ($request->filled('payment_method')) {
            $query->forReportedMethod($request->payment_method);
        }

        if ($request->filled('driver')) {
            $query->where('driver', $request->driver);
        }
    }

    private function compareMetric(int $current, int $previous): array
    {
        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $current - $previous,
            'change_percent' => $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : ($current > 0 ? 100.0 : 0.0),
        ];
    }

    private function buildDailySalesSeries(string $dateFrom, string $dateTo, array $amountByDate, array $countByDate): array
    {
        return collect(CarbonPeriod::create($dateFrom, $dateTo))->map(function ($date) use ($amountByDate, $countByDate) {
            $key = $date->toDateString();

            return [
                'date' => $key,
                'total' => (int) ($amountByDate[$key] ?? 0),
                'count' => (int) ($countByDate[$key] ?? 0),
            ];
        })->values()->all();
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

        foreach (CarbonPeriod::create($dateFrom, $dateTo) as $date) {
            $key = $date->toDateString();
            $labels[] = $key;
            $currentAmount[] = (int) ($currentAmounts[$key] ?? 0);
            $currentCount[] = (int) ($currentCounts[$key] ?? 0);

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

    private function buildHourlySales(Builder $query): array
    {
        $raw = (clone $query)
            ->select(DB::raw(SqlDialect::hour('paid_at').' as hour'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->get()
            ->keyBy('hour');

        return collect(range(0, 23))->map(function ($hour) use ($raw) {
            $row = $raw[$hour] ?? null;

            return [
                'hour' => $hour,
                'label' => sprintf('%02d:00', $hour),
                'total' => (int) ($row->total ?? 0),
                'count' => (int) ($row->count ?? 0),
            ];
        })->values()->all();
    }

    private function buildMonthlySales(Builder $query, string $dateFrom, string $dateTo, int $periodDays): array
    {
        if ($periodDays < 32) {
            return [];
        }

        $raw = (clone $query)
            ->select(DB::raw(SqlDialect::yearMonth('paid_at').' as month'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return $raw->map(fn ($row) => [
            'month' => $row->month,
            'label' => Carbon::createFromFormat('Y-m', $row->month)->locale('fa')->translatedFormat('M Y'),
            'total' => (int) $row->total,
            'count' => (int) $row->count,
        ])->values()->all();
    }

    private function buildSalesHeatmap(Builder $query): array
    {
        $weekdayLabels = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
        $raw = (clone $query)
            ->select(
                DB::raw(SqlDialect::dayOfWeek('paid_at').' as weekday'),
                DB::raw(SqlDialect::hour('paid_at').' as hour'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('weekday', 'hour')
            ->get();

        $cells = [];
        $max = 0;

        foreach (range(1, 7) as $day) {
            foreach (range(0, 23) as $hour) {
                $row = $raw->first(fn ($r) => (int) $r->weekday === $day && (int) $r->hour === $hour);
                $count = (int) ($row->count ?? 0);
                $max = max($max, $count);
                $cells[] = [
                    'weekday' => $day,
                    'weekday_label' => $weekdayLabels[$day - 1],
                    'hour' => $hour,
                    'count' => $count,
                    'total' => (int) ($row->total ?? 0),
                ];
            }
        }

        return ['cells' => $cells, 'max' => $max, 'weekday_labels' => $weekdayLabels];
    }

    private function buildSalesInsights(Builder $query, int $netSales, int $uniqueBuyers, int $totalTransactions, int $totalOriginalSales, int $totalDiscount): array
    {
        $byDay = (clone $query)
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderByDesc('total')
            ->get();

        $peakDay = $byDay->first();
        $byHour = (clone $query)
            ->select(DB::raw(SqlDialect::hour('paid_at').' as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderByDesc('count')
            ->first();

        return [
            'avg_per_buyer' => $uniqueBuyers > 0 ? (int) round($netSales / $uniqueBuyers) : 0,
            'avg_transactions_per_buyer' => $uniqueBuyers > 0 ? round($totalTransactions / $uniqueBuyers, 2) : 0,
            'discount_rate' => $totalOriginalSales > 0 ? round(($totalDiscount / $totalOriginalSales) * 100, 1) : 0,
            'peak_day' => $peakDay ? Carbon::parse($peakDay->date)->locale('fa')->translatedFormat('j F') : '—',
            'peak_day_total' => (int) ($peakDay->total ?? 0),
            'peak_day_count' => (int) ($peakDay->count ?? 0),
            'peak_hour' => $byHour ? sprintf('%02d:00', $byHour->hour) : '—',
            'peak_hour_count' => (int) ($byHour->count ?? 0),
        ];
    }

    private function scopedPaidPaymentsQuery(): Builder
    {
        return $this->contentScope()->applyToPayments(
            Payment::query()->where('status', 1)->whereNotNull('paid_at')
        );
    }
}

