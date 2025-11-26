<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\VideoView;
use App\Models\Comment;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Like;
use App\Models\Payment;
use App\Models\Rating;
use App\Models\Certificate;
use App\Models\Report;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserActivityReportController extends Controller
{
    /**
     * Get user activity report with filters and pagination
     */
    public function activities(Request $request)
    {
        $query = User::withCount([
            'logins',
            'videoViews',
            'comments',
            'questions',
            'answers',
            'likes',
            'payments',
            'ratings',
            'certificates',
            'reports'
        ]);

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        // Filter by active status
        if ($request->filled('active_status')) {
            if ($request->active_status === 'active') {
                $query->where('active', true);
            } elseif ($request->active_status === 'inactive') {
                $query->where('active', false);
            }
        }

        // Filter by VIP status
        if ($request->filled('vip_status')) {
            if ($request->vip_status === 'vip') {
                $query->whereHas('plans', function($q) {
                    $q->where('plan_user.expired_at', '>', now());
                });
            } elseif ($request->vip_status === 'non_vip') {
                $query->whereDoesntHave('plans', function($q) {
                    $q->where('plan_user.expired_at', '>', now());
                });
            }
        }

        // Filter by spent amount
        if ($request->filled('spent_min')) {
            $userIdsWithSpent = Payment::where('status', 1)
                ->whereNotNull('paid_at')
                ->select('user_id', DB::raw('SUM(amount) as total'))
                ->groupBy('user_id')
                ->having('total', '>=', $request->spent_min)
                ->pluck('user_id');
            $query->whereIn('id', $userIdsWithSpent);
        }

        if ($request->filled('spent_max')) {
            $userIdsWithSpent = Payment::where('status', 1)
                ->whereNotNull('paid_at')
                ->select('user_id', DB::raw('SUM(amount) as total'))
                ->groupBy('user_id')
                ->having('total', '<=', $request->spent_max)
                ->pluck('user_id');
            $query->whereIn('id', $userIdsWithSpent);
        }

        // Filter by registration date
        if ($request->filled('registration_date_from')) {
            $query->whereDate('created_at', '>=', $request->registration_date_from);
        }

        if ($request->filled('registration_date_to')) {
            $query->whereDate('created_at', '<=', $request->registration_date_to);
        }

        // Filter by inactivity days
        if ($request->filled('inactive_days')) {
            $inactiveDate = now()->subDays($request->inactive_days);
            $query->where(function($q) use ($inactiveDate) {
                $q->whereNull('last_seen')
                  ->orWhere('last_seen', '<', $inactiveDate);
            });
        }

        // Filter by activity type
        if ($request->filled('activity_type')) {
            $activityType = $request->activity_type;
            switch ($activityType) {
                case 'active':
                    $query->whereHas('logins', function($q) use ($request) {
                        if ($request->filled('date_from')) {
                            $q->whereDate('logged_in_at', '>=', $request->date_from);
                        }
                        if ($request->filled('date_to')) {
                            $q->whereDate('logged_in_at', '<=', $request->date_to);
                        }
                    });
                    break;
                case 'viewers':
                    $query->whereHas('videoViews', function($q) use ($request) {
                        if ($request->filled('date_from')) {
                            $q->whereDate('updated_at', '>=', $request->date_from);
                        }
                        if ($request->filled('date_to')) {
                            $q->whereDate('updated_at', '<=', $request->date_to);
                        }
                    });
                    break;
                case 'commenters':
                    $query->whereHas('comments', function($q) use ($request) {
                        if ($request->filled('date_from')) {
                            $q->whereDate('created_at', '>=', $request->date_from);
                        }
                        if ($request->filled('date_to')) {
                            $q->whereDate('created_at', '<=', $request->date_to);
                        }
                    });
                    break;
                case 'purchasers':
                    $query->whereHas('payments', function($q) use ($request) {
                        $q->where('status', 1)->whereNotNull('paid_at');
                        if ($request->filled('date_from')) {
                            $q->whereDate('paid_at', '>=', $request->date_from);
                        }
                        if ($request->filled('date_to')) {
                            $q->whereDate('paid_at', '<=', $request->date_to);
                        }
                    });
                    break;
                case 'new_users':
                    if ($request->filled('date_from')) {
                        $query->whereDate('created_at', '>=', $request->date_from);
                    }
                    if ($request->filled('date_to')) {
                        $query->whereDate('created_at', '<=', $request->date_to);
                    }
                    break;
                case 'inactive':
                    $inactiveDate = now()->subDays(30);
                    $query->where(function($q) use ($inactiveDate) {
                        $q->whereNull('last_seen')
                          ->orWhere('last_seen', '<', $inactiveDate);
                    });
                    break;
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'most_active':
                $query->orderBy('logins_count', 'desc');
                break;
            case 'most_views':
                $query->orderBy('video_views_count', 'desc');
                break;
            case 'most_comments':
                $query->orderBy('comments_count', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = $request->input('perPage', 20);
        $users = $query->paginate($perPage);

        $data = collect($users->items())->map(function ($user) use ($request) {
            $dateFrom = $request->date_from ?? null;
            $dateTo = $request->date_to ?? null;

            // Get detailed activity counts for the period
            $loginCount = UserLogin::where('user_id', $user->id);
            $viewCount = VideoView::where('user_id', $user->id);
            $commentCount = Comment::where('user_id', $user->id);
            $questionCount = Question::where('user_id', $user->id);
            $answerCount = Answer::where('user_id', $user->id);
            $likeCount = Like::where('user_id', $user->id);
            $paymentCount = Payment::where('user_id', $user->id)->where('status', 1)->whereNotNull('paid_at');
            $ratingCount = Rating::where('user_id', $user->id);
            $certificateCount = Certificate::where('user_id', $user->id);

            if ($dateFrom) {
                $loginCount->whereDate('logged_in_at', '>=', $dateFrom);
                $viewCount->whereDate('updated_at', '>=', $dateFrom);
                $commentCount->whereDate('created_at', '>=', $dateFrom);
                $questionCount->whereDate('created_at', '>=', $dateFrom);
                $answerCount->whereDate('created_at', '>=', $dateFrom);
                $likeCount->whereDate('created_at', '>=', $dateFrom);
                $paymentCount->whereDate('paid_at', '>=', $dateFrom);
                $ratingCount->whereDate('created_at', '>=', $dateFrom);
                $certificateCount->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $loginCount->whereDate('logged_in_at', '<=', $dateTo);
                $viewCount->whereDate('updated_at', '<=', $dateTo);
                $commentCount->whereDate('created_at', '<=', $dateTo);
                $questionCount->whereDate('created_at', '<=', $dateTo);
                $answerCount->whereDate('created_at', '<=', $dateTo);
                $likeCount->whereDate('created_at', '<=', $dateTo);
                $paymentCount->whereDate('paid_at', '<=', $dateTo);
                $ratingCount->whereDate('created_at', '<=', $dateTo);
                $certificateCount->whereDate('created_at', '<=', $dateTo);
            }

            // Get total watch time
            $totalWatchTime = VideoView::where('user_id', $user->id);
            if ($dateFrom) {
                $totalWatchTime->whereDate('updated_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $totalWatchTime->whereDate('updated_at', '<=', $dateTo);
            }
            $videoViews = $totalWatchTime->get();
            $watchTimeSeconds = 0;
            foreach ($videoViews as $view) {
                $watchedTimes = json_decode($view->watched_times, true) ?? [];
                $watchTimeSeconds += count($watchedTimes);
            }

            // Get total spent
            $totalSpent = Payment::where('user_id', $user->id)
                ->where('status', 1)
                ->whereNotNull('paid_at');
            if ($dateFrom) {
                $totalSpent->whereDate('paid_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $totalSpent->whereDate('paid_at', '<=', $dateTo);
            }
            $spent = $totalSpent->sum('amount');

            // Get last activity
            $lastLogin = UserLogin::where('user_id', $user->id)->latest('logged_in_at')->first();
            $lastView = VideoView::where('user_id', $user->id)->latest('updated_at')->first();
            $lastComment = Comment::where('user_id', $user->id)->latest('created_at')->first();
            
            $lastActivity = null;
            $lastActivityDate = null;
            
            $activities = [
                ['date' => $lastLogin?->logged_in_at, 'type' => 'login'],
                ['date' => $lastView?->updated_at, 'type' => 'view'],
                ['date' => $lastComment?->created_at, 'type' => 'comment'],
            ];
            
            $latest = collect($activities)->filter(fn($a) => $a['date'])->sortByDesc('date')->first();
            if ($latest) {
                $lastActivity = $latest['type'];
                $lastActivityDate = $latest['date'];
            }

            // Get VIP status
            $hasVip = $user->hasVip();
            
            // Get completed courses count
            $completedCourses = $user->completedCourses()->count();
            
            // Get purchased courses count
            $purchasedCourses = $user->courses()->count();
            
            // Get last login details
            $lastLoginDetails = $lastLogin ? [
                'logged_in_at' => $lastLogin->logged_in_at,
                'ip_address' => $lastLogin->ip_address,
                'device' => $lastLogin->device,
            ] : null;

            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'profile_pic' => $user->profile_pic,
                'created_at' => $user->created_at,
                'last_seen' => $user->last_seen,
                'active' => $user->active,
                'role' => $user->role,
                'has_vip' => $hasVip,
                'completed_courses' => $completedCourses,
                'purchased_courses' => $purchasedCourses,
                'activities' => [
                    'logins' => $loginCount->count(),
                    'video_views' => $viewCount->count(),
                    'comments' => $commentCount->count(),
                    'questions' => $questionCount->count(),
                    'answers' => $answerCount->count(),
                    'likes' => $likeCount->count(),
                    'payments' => $paymentCount->count(),
                    'ratings' => $ratingCount->count(),
                    'certificates' => $certificateCount->count(),
                    'total_interactions' => $commentCount->count() + $questionCount->count() + $answerCount->count() + $likeCount->count(),
                ],
                'total_watch_time_seconds' => $watchTimeSeconds,
                'total_spent' => $spent,
                'last_activity_type' => $lastActivity,
                'last_activity_date' => $lastActivityDate,
                'last_login_details' => $lastLoginDetails,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'activities' => $data,
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ]
        ], 200);
    }

    /**
     * Get user activity statistics
     */
    public function stats(Request $request)
    {
        $baseQuery = User::query();
        
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $userIds = $baseQuery->pluck('id');

        // Total users
        $totalUsers = $baseQuery->count();

        // Active users (logged in within date range)
        $activeUsers = UserLogin::whereIn('user_id', $userIds);
        if ($request->filled('date_from')) {
            $activeUsers->whereDate('logged_in_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $activeUsers->whereDate('logged_in_at', '<=', $request->date_to);
        }
        $activeUsersCount = $activeUsers->distinct('user_id')->count('user_id');

        // Total activities
        $totalLogins = UserLogin::whereIn('user_id', $userIds);
        $totalViews = VideoView::whereIn('user_id', $userIds);
        $totalComments = Comment::whereIn('user_id', $userIds);
        $totalQuestions = Question::whereIn('user_id', $userIds);
        $totalAnswers = Answer::whereIn('user_id', $userIds);
        $totalLikes = Like::whereIn('user_id', $userIds);
        $totalPayments = Payment::whereIn('user_id', $userIds)->where('status', 1)->whereNotNull('paid_at');
        $totalRatings = Rating::whereIn('user_id', $userIds);
        $totalCertificates = Certificate::whereIn('user_id', $userIds);

        if ($request->filled('date_from')) {
            $totalLogins->whereDate('logged_in_at', '>=', $request->date_from);
            $totalViews->whereDate('updated_at', '>=', $request->date_from);
            $totalComments->whereDate('created_at', '>=', $request->date_from);
            $totalQuestions->whereDate('created_at', '>=', $request->date_from);
            $totalAnswers->whereDate('created_at', '>=', $request->date_from);
            $totalLikes->whereDate('created_at', '>=', $request->date_from);
            $totalPayments->whereDate('paid_at', '>=', $request->date_from);
            $totalRatings->whereDate('created_at', '>=', $request->date_from);
            $totalCertificates->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $totalLogins->whereDate('logged_in_at', '<=', $request->date_to);
            $totalViews->whereDate('updated_at', '<=', $request->date_to);
            $totalComments->whereDate('created_at', '<=', $request->date_to);
            $totalQuestions->whereDate('created_at', '<=', $request->date_to);
            $totalAnswers->whereDate('created_at', '<=', $request->date_to);
            $totalLikes->whereDate('created_at', '<=', $request->date_to);
            $totalPayments->whereDate('paid_at', '<=', $request->date_to);
            $totalRatings->whereDate('created_at', '<=', $request->date_to);
            $totalCertificates->whereDate('created_at', '<=', $request->date_to);
        }

        // Calculate total watch time
        $videoViews = $totalViews->get();
        $totalWatchTimeSeconds = 0;
        foreach ($videoViews as $view) {
            $watchedTimes = json_decode($view->watched_times, true) ?? [];
            $totalWatchTimeSeconds += count($watchedTimes);
        }

        // Total revenue from active users
        $totalRevenue = $totalPayments->sum('amount');

        // Average activities per user
        $avgActivitiesPerUser = $totalUsers > 0 
            ? ($totalLogins->count() + $totalViews->count() + $totalComments->count() + $totalQuestions->count() + $totalAnswers->count() + $totalLikes->count()) / $totalUsers 
            : 0;

        // Daily activity (last 30 days)
        $dailyActivity = [];
        $startDate = $request->filled('date_from') 
            ? \Carbon\Carbon::parse($request->date_from) 
            : now()->subDays(30);
        $endDate = $request->filled('date_to') 
            ? \Carbon\Carbon::parse($request->date_to) 
            : now();

        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $date) {
            $day = $date->format('Y-m-d');
            
            $dayLogins = UserLogin::whereIn('user_id', $userIds)
                ->whereDate('logged_in_at', $day)
                ->count();
            
            $dayViews = VideoView::whereIn('user_id', $userIds)
                ->whereDate('updated_at', $day)
                ->count();
            
            $dayComments = Comment::whereIn('user_id', $userIds)
                ->whereDate('created_at', $day)
                ->count();

            $dailyActivity[] = [
                'date' => $day,
                'logins' => $dayLogins,
                'views' => $dayViews,
                'comments' => $dayComments,
                'total' => $dayLogins + $dayViews + $dayComments,
            ];
        }

        // Comparison stats (previous period)
        $comparisonStats = null;
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $currentStart = \Carbon\Carbon::parse($request->date_from);
            $currentEnd = \Carbon\Carbon::parse($request->date_to);
            $periodDays = $currentStart->diffInDays($currentEnd);
            
            $previousStart = $currentStart->copy()->subDays($periodDays + 1);
            $previousEnd = $currentStart->copy()->subDay();
            
            $previousUserIds = User::whereDate('created_at', '>=', $previousStart)
                ->whereDate('created_at', '<=', $previousEnd)
                ->pluck('id');
            
            $previousLogins = UserLogin::whereIn('user_id', $previousUserIds)
                ->whereDate('logged_in_at', '>=', $previousStart)
                ->whereDate('logged_in_at', '<=', $previousEnd)
                ->distinct('user_id')
                ->count('user_id');
            
            $previousViews = VideoView::whereIn('user_id', $previousUserIds)
                ->whereDate('updated_at', '>=', $previousStart)
                ->whereDate('updated_at', '<=', $previousEnd)
                ->count();
            
            $previousComments = Comment::whereIn('user_id', $previousUserIds)
                ->whereDate('created_at', '>=', $previousStart)
                ->whereDate('created_at', '<=', $previousEnd)
                ->count();
            
            $previousRevenue = Payment::whereIn('user_id', $previousUserIds)
                ->where('status', 1)
                ->whereNotNull('paid_at')
                ->whereDate('paid_at', '>=', $previousStart)
                ->whereDate('paid_at', '<=', $previousEnd)
                ->sum('amount');
            
            $comparisonStats = [
                'active_users' => [
                    'current' => $activeUsersCount,
                    'previous' => $previousLogins,
                    'change' => $activeUsersCount - $previousLogins,
                    'change_percent' => $previousLogins > 0 ? round((($activeUsersCount - $previousLogins) / $previousLogins) * 100, 2) : 0,
                ],
                'video_views' => [
                    'current' => $totalViews->count(),
                    'previous' => $previousViews,
                    'change' => $totalViews->count() - $previousViews,
                    'change_percent' => $previousViews > 0 ? round((($totalViews->count() - $previousViews) / $previousViews) * 100, 2) : 0,
                ],
                'comments' => [
                    'current' => $totalComments->count(),
                    'previous' => $previousComments,
                    'change' => $totalComments->count() - $previousComments,
                    'change_percent' => $previousComments > 0 ? round((($totalComments->count() - $previousComments) / $previousComments) * 100, 2) : 0,
                ],
                'revenue' => [
                    'current' => $totalRevenue,
                    'previous' => $previousRevenue,
                    'change' => $totalRevenue - $previousRevenue,
                    'change_percent' => $previousRevenue > 0 ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 2) : 0,
                ],
            ];
        }

        // Activity distribution for charts
        $activityDistribution = [
            'labels' => ['لاگین', 'تماشا', 'کامنت', 'سوال', 'جواب', 'لایک', 'پرداخت', 'امتیاز', 'گواهینامه'],
            'data' => [
                $totalLogins->count(),
                $totalViews->count(),
                $totalComments->count(),
                $totalQuestions->count(),
                $totalAnswers->count(),
                $totalLikes->count(),
                $totalPayments->count(),
                $totalRatings->count(),
                $totalCertificates->count(),
            ],
            'colors' => ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4', '#34d399', '#fbbf24', '#c084fc'],
        ];

        // User status distribution
        $activeUsersCount = User::whereIn('id', $userIds)->where('active', true)->count();
        $inactiveUsersCount = User::whereIn('id', $userIds)->where('active', false)->count();
        $statusDistribution = [
            'labels' => ['فعال', 'غیرفعال'],
            'data' => [$activeUsersCount, $inactiveUsersCount],
            'colors' => ['#10b981', '#ef4444'],
        ];

        // VIP vs Non-VIP
        $vipUsersCount = User::whereIn('id', $userIds)
            ->whereHas('plans', function($q) {
                $q->where('plan_user.expired_at', '>', now());
            })
            ->count();
        $nonVipUsersCount = $totalUsers - $vipUsersCount;
        $vipDistribution = [
            'labels' => ['VIP', 'عادی'],
            'data' => [$vipUsersCount, $nonVipUsersCount],
            'colors' => ['#f59e0b', '#6b7280'],
        ];

        return response()->json([
            'message' => 'Success',
            'stats' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsersCount,
                'total_activities' => [
                    'logins' => $totalLogins->count(),
                    'video_views' => $totalViews->count(),
                    'comments' => $totalComments->count(),
                    'questions' => $totalQuestions->count(),
                    'answers' => $totalAnswers->count(),
                    'likes' => $totalLikes->count(),
                    'payments' => $totalPayments->count(),
                    'ratings' => $totalRatings->count(),
                    'certificates' => $totalCertificates->count(),
                ],
                'total_watch_time_seconds' => $totalWatchTimeSeconds,
                'total_revenue' => $totalRevenue,
                'average_activities_per_user' => round($avgActivitiesPerUser, 2),
                'daily_activity' => $dailyActivity,
                'comparison' => $comparisonStats,
                'activity_distribution' => $activityDistribution,
                'status_distribution' => $statusDistribution,
                'vip_distribution' => $vipDistribution,
            ]
        ], 200);
    }

    /**
     * Get advanced analytics
     */
    public function analytics(Request $request)
    {
        $baseQuery = User::query();
        
        if ($request->filled('date_from')) {
            $baseQuery->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $baseQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $userIds = $baseQuery->pluck('id');

        // Most active users
        $mostActiveUsers = User::whereIn('id', $userIds)
            ->withCount([
                'logins' => function($q) use ($request) {
                    if ($request->filled('date_from')) {
                        $q->whereDate('logged_in_at', '>=', $request->date_from);
                    }
                    if ($request->filled('date_to')) {
                        $q->whereDate('logged_in_at', '<=', $request->date_to);
                    }
                },
                'videoViews' => function($q) use ($request) {
                    if ($request->filled('date_from')) {
                        $q->whereDate('updated_at', '>=', $request->date_from);
                    }
                    if ($request->filled('date_to')) {
                        $q->whereDate('updated_at', '<=', $request->date_to);
                    }
                },
                'comments' => function($q) use ($request) {
                    if ($request->filled('date_from')) {
                        $q->whereDate('created_at', '>=', $request->date_from);
                    }
                    if ($request->filled('date_to')) {
                        $q->whereDate('created_at', '<=', $request->date_to);
                    }
                },
            ])
            ->orderByRaw('(logins_count + video_views_count + comments_count) DESC')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'total_activities' => $user->logins_count + $user->video_views_count + $user->comments_count,
                    'logins' => $user->logins_count,
                    'views' => $user->video_views_count,
                    'comments' => $user->comments_count,
                ];
            });

        // Top viewers
        $topViewers = VideoView::whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('COUNT(*) as view_count'))
            ->groupBy('user_id')
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($view) {
                $user = User::find($view->user_id);
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'view_count' => $view->view_count,
                ];
            });

        // Top commenters
        $topCommenters = Comment::whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('COUNT(*) as comment_count'))
            ->groupBy('user_id')
            ->orderBy('comment_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($comment) {
                $user = User::find($comment->user_id);
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'comment_count' => $comment->comment_count,
                ];
            });

        // Top question askers
        $topQuestioners = Question::whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('COUNT(*) as question_count'))
            ->groupBy('user_id')
            ->orderBy('question_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($question) {
                $user = User::find($question->user_id);
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'question_count' => $question->question_count,
                ];
            });

        // Top answerers
        $topAnswerers = Answer::whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('COUNT(*) as answer_count'))
            ->groupBy('user_id')
            ->orderBy('answer_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($answer) {
                $user = User::find($answer->user_id);
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'answer_count' => $answer->answer_count,
                ];
            });

        // Activity by hour of day
        $activityByHour = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourLogins = UserLogin::whereIn('user_id', $userIds)
                ->whereRaw('HOUR(logged_in_at) = ?', [$hour])
                ->count();
            
            $hourComments = Comment::whereIn('user_id', $userIds)
                ->whereRaw('HOUR(created_at) = ?', [$hour])
                ->count();

            $activityByHour[] = [
                'hour' => $hour,
                'logins' => $hourLogins,
                'comments' => $hourComments,
                'total' => $hourLogins + $hourComments,
            ];
        }

        // Activity by day of week
        $activityByDay = [];
        $days = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'];
        for ($day = 0; $day < 7; $day++) {
            $dayLogins = UserLogin::whereIn('user_id', $userIds)
                ->whereRaw('DAYOFWEEK(logged_in_at) = ?', [$day + 1])
                ->count();
            
            $dayComments = Comment::whereIn('user_id', $userIds)
                ->whereRaw('DAYOFWEEK(created_at) = ?', [$day + 1])
                ->count();

            $activityByDay[] = [
                'day' => $days[$day],
                'day_number' => $day,
                'logins' => $dayLogins,
                'comments' => $dayComments,
                'total' => $dayLogins + $dayComments,
            ];
        }

        // Top watch time users
        $topWatchTimeUsers = VideoView::whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('COUNT(*) as view_count'))
            ->groupBy('user_id')
            ->orderBy('view_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($view) {
                $user = User::find($view->user_id);
                $videoViews = VideoView::where('user_id', $view->user_id)->get();
                $watchTimeSeconds = 0;
                foreach ($videoViews as $v) {
                    $watchedTimes = json_decode($v->watched_times, true) ?? [];
                    $watchTimeSeconds += count($watchedTimes);
                }
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'watch_time_seconds' => $watchTimeSeconds,
                    'view_count' => $view->view_count,
                ];
            })
            ->sortByDesc('watch_time_seconds')
            ->values()
            ->take(10);

        // Top certificate holders
        $topCertificateHolders = Certificate::whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('COUNT(*) as certificate_count'))
            ->groupBy('user_id')
            ->orderBy('certificate_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($cert) {
                $user = User::find($cert->user_id);
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'certificate_count' => $cert->certificate_count,
                ];
            });

        // Top rated users (users who gave most ratings)
        $topRaters = Rating::whereIn('user_id', $userIds)
            ->select('user_id', DB::raw('COUNT(*) as rating_count'))
            ->groupBy('user_id')
            ->orderBy('rating_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($rating) {
                $user = User::find($rating->user_id);
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'rating_count' => $rating->rating_count,
                ];
            });

        // Most interactive users (likes + comments + questions + answers)
        $mostInteractiveUsers = User::whereIn('id', $userIds)
            ->withCount([
                'likes' => function($q) use ($request) {
                    if ($request->filled('date_from')) {
                        $q->whereDate('created_at', '>=', $request->date_from);
                    }
                    if ($request->filled('date_to')) {
                        $q->whereDate('created_at', '<=', $request->date_to);
                    }
                },
                'comments' => function($q) use ($request) {
                    if ($request->filled('date_from')) {
                        $q->whereDate('created_at', '>=', $request->date_from);
                    }
                    if ($request->filled('date_to')) {
                        $q->whereDate('created_at', '<=', $request->date_to);
                    }
                },
                'questions' => function($q) use ($request) {
                    if ($request->filled('date_from')) {
                        $q->whereDate('created_at', '>=', $request->date_from);
                    }
                    if ($request->filled('date_to')) {
                        $q->whereDate('created_at', '<=', $request->date_to);
                    }
                },
                'answers' => function($q) use ($request) {
                    if ($request->filled('date_from')) {
                        $q->whereDate('created_at', '>=', $request->date_from);
                    }
                    if ($request->filled('date_to')) {
                        $q->whereDate('created_at', '<=', $request->date_to);
                    }
                },
            ])
            ->orderByRaw('(likes_count + comments_count + questions_count + answers_count) DESC')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'total_interactions' => $user->likes_count + $user->comments_count + $user->questions_count + $user->answers_count,
                    'likes' => $user->likes_count,
                    'comments' => $user->comments_count,
                    'questions' => $user->questions_count,
                    'answers' => $user->answers_count,
                ];
            });

        // New users (in date range)
        $newUsers = User::whereIn('id', $userIds)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'created_at' => $user->created_at,
                ];
            });

        // Inactive users (no activity in last 30 days)
        $inactiveUsers = User::whereIn('id', $userIds)
            ->where(function($q) {
                $inactiveDate = now()->subDays(30);
                $q->whereNull('last_seen')
                  ->orWhere('last_seen', '<', $inactiveDate);
            })
            ->orderBy('last_seen', 'asc')
            ->limit(10)
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'last_seen' => $user->last_seen,
                ];
            });

        // VIP users
        $vipUsers = User::whereIn('id', $userIds)
            ->whereHas('plans', function($q) {
                $q->where('plan_user.expired_at', '>', now());
            })
            ->with(['plans' => function($q) {
                $q->wherePivot('expired_at', '>', now());
            }])
            ->limit(10)
            ->get()
            ->map(function($user) {
                $activePlan = $user->plans->first();
                return [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_pic' => $user->profile_pic,
                    'plan' => $activePlan ? [
                        'id' => $activePlan->id,
                        'title' => $activePlan->title,
                        'expired_at' => $activePlan->pivot->expired_at,
                    ] : null,
                ];
            });

        return response()->json([
            'message' => 'Success',
            'analytics' => [
                'most_active_users' => $mostActiveUsers,
                'top_viewers' => $topViewers,
                'top_commenters' => $topCommenters,
                'top_questioners' => $topQuestioners,
                'top_answerers' => $topAnswerers,
                'top_watch_time_users' => $topWatchTimeUsers,
                'top_certificate_holders' => $topCertificateHolders,
                'top_raters' => $topRaters,
                'most_interactive_users' => $mostInteractiveUsers,
                'new_users' => $newUsers,
                'inactive_users' => $inactiveUsers,
                'vip_users' => $vipUsers,
                'activity_by_hour' => $activityByHour,
                'activity_by_day' => $activityByDay,
            ]
        ], 200);
    }

    /**
     * Export user activity report
     */
    public function export(Request $request)
    {
        $query = User::query();

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->get();

        $data = $users->map(function ($user) use ($request) {
            $dateFrom = $request->date_from ?? null;
            $dateTo = $request->date_to ?? null;

            $loginCount = UserLogin::where('user_id', $user->id);
            $viewCount = VideoView::where('user_id', $user->id);
            $commentCount = Comment::where('user_id', $user->id);
            $questionCount = Question::where('user_id', $user->id);
            $answerCount = Answer::where('user_id', $user->id);
            $likeCount = Like::where('user_id', $user->id);
            $paymentCount = Payment::where('user_id', $user->id)->where('status', 1)->whereNotNull('paid_at');

            if ($dateFrom) {
                $loginCount->whereDate('logged_in_at', '>=', $dateFrom);
                $viewCount->whereDate('updated_at', '>=', $dateFrom);
                $commentCount->whereDate('created_at', '>=', $dateFrom);
                $questionCount->whereDate('created_at', '>=', $dateFrom);
                $answerCount->whereDate('created_at', '>=', $dateFrom);
                $likeCount->whereDate('created_at', '>=', $dateFrom);
                $paymentCount->whereDate('paid_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $loginCount->whereDate('logged_in_at', '<=', $dateTo);
                $viewCount->whereDate('updated_at', '<=', $dateTo);
                $commentCount->whereDate('created_at', '<=', $dateTo);
                $questionCount->whereDate('created_at', '<=', $dateTo);
                $answerCount->whereDate('created_at', '<=', $dateTo);
                $likeCount->whereDate('created_at', '<=', $dateTo);
                $paymentCount->whereDate('paid_at', '<=', $dateTo);
            }

            $totalSpent = $paymentCount->sum('amount');

            // Format dates safely
            $createdAt = $user->created_at;
            if (is_string($createdAt)) {
                $createdAt = \Carbon\Carbon::parse($createdAt);
            }
            
            $lastSeen = $user->last_seen;
            if ($lastSeen) {
                if (is_string($lastSeen)) {
                    $lastSeen = \Carbon\Carbon::parse($lastSeen);
                }
                $lastSeenFormatted = $lastSeen->format('Y-m-d H:i:s');
            } else {
                $lastSeenFormatted = 'نامشخص';
            }

            return [
                'name' => $user->first_name . ' ' . $user->last_name,
                'username' => $user->username,
                'email' => $user->email,
                'created_at' => $createdAt->format('Y-m-d H:i:s'),
                'last_seen' => $lastSeenFormatted,
                'logins' => $loginCount->count(),
                'video_views' => $viewCount->count(),
                'comments' => $commentCount->count(),
                'questions' => $questionCount->count(),
                'answers' => $answerCount->count(),
                'likes' => $likeCount->count(),
                'payments' => $paymentCount->count(),
                'total_spent' => $totalSpent,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'data' => $data
        ], 200);
    }
}

