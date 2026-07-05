<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\AppliesContentScope;
use App\Models\Answer;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Like;
use App\Models\Question;
use App\Models\User;
use App\Models\View;
use App\Support\SqlDialect;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LaravelInteraction\Bookmark\Bookmark;

class EngagementController extends Controller
{
    use AppliesContentScope;

    private const LIKE_TYPE_MAP = [
        Course::class => [
            'label' => 'دوره',
            'short' => 'Course',
            'title_field' => 'title',
            'slug_field' => 'slug',
        ],
        Episode::class => [
            'label' => 'قسمت',
            'short' => 'Episode',
            'title_field' => 'title',
            'slug_field' => 'slug',
        ],
        Question::class => [
            'label' => 'پرسش',
            'short' => 'Question',
            'title_field' => 'subject',
            'slug_field' => 'slug',
        ],
        Article::class => [
            'label' => 'مقاله',
            'short' => 'Article',
            'title_field' => 'title',
            'slug_field' => 'slug',
        ],
        Answer::class => [
            'label' => 'پاسخ',
            'short' => 'Answer',
            'title_field' => 'answer',
            'slug_field' => null,
        ],
        Comment::class => [
            'label' => 'نظر',
            'short' => 'Comment',
            'title_field' => 'comment',
            'slug_field' => null,
        ],
    ];

    private const BOOKMARK_TYPE_MAP = [
        Course::class => [
            'label' => 'دوره',
            'short' => 'Course',
            'title_field' => 'title',
            'slug_field' => 'slug',
        ],
        Episode::class => [
            'label' => 'قسمت',
            'short' => 'Episode',
            'title_field' => 'title',
            'slug_field' => 'slug',
        ],
        Question::class => [
            'label' => 'پرسش',
            'short' => 'Question',
            'title_field' => 'subject',
            'slug_field' => 'slug',
        ],
        Article::class => [
            'label' => 'مقاله',
            'short' => 'Article',
            'title_field' => 'title',
            'slug_field' => 'slug',
        ],
    ];

    private const OVERVIEW_CONTENT_TYPES = [
        Course::class => ['label' => 'دوره', 'short' => 'Course'],
        Episode::class => ['label' => 'قسمت', 'short' => 'Episode'],
        Question::class => ['label' => 'پرسش', 'short' => 'Question'],
        Article::class => ['label' => 'مقاله', 'short' => 'Article'],
    ];

    public function overviewStats(Request $request)
    {
        [$dateFrom, $dateTo, $periodDays, $previousFrom, $previousTo] = $this->resolveDateRange($request);

        $likeQuery = $this->scopedLikesQuery();
        $this->applyDateRange($likeQuery, $dateFrom, $dateTo);
        $commentQuery = $this->scopedCommentsQuery();
        $this->applyDateRange($commentQuery, $dateFrom, $dateTo);
        $viewQuery = $this->scopedViewsQuery();
        $this->applyDateRange($viewQuery, $dateFrom, $dateTo);
        $bookmarkQuery = $this->scopedBookmarksQuery();
        $this->applyDateRange($bookmarkQuery, $dateFrom, $dateTo);

        $likesTotal = (clone $likeQuery)->count();
        $likesPositive = (clone $likeQuery)->where('type', 'like')->count();
        $dislikesTotal = (clone $likeQuery)->where('type', 'dislike')->count();
        $commentsTotal = (clone $commentQuery)->count();
        $viewsTotal = (clone $viewQuery)->count();
        $bookmarksTotal = (clone $bookmarkQuery)->count();

        $prevLikes = $this->scopedLikesQuery()->tap(fn ($q) => $this->applyDateRange($q, $previousFrom, $previousTo))->count();
        $prevComments = $this->scopedCommentsQuery()->tap(fn ($q) => $this->applyDateRange($q, $previousFrom, $previousTo))->count();
        $prevViews = $this->scopedViewsQuery()->tap(fn ($q) => $this->applyDateRange($q, $previousFrom, $previousTo))->count();
        $prevBookmarks = $this->scopedBookmarksQuery()->tap(fn ($q) => $this->applyDateRange($q, $previousFrom, $previousTo))->count();

        $likeDailyRaw = (clone $likeQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')->pluck('count', 'date');
        $commentDailyRaw = (clone $commentQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')->pluck('count', 'date');
        $viewDailyRaw = (clone $viewQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')->pluck('count', 'date');
        $bookmarkDailyRaw = (clone $bookmarkQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')->pluck('count', 'date');

        $period = CarbonPeriod::create($dateFrom, $dateTo);
        $dailyCombined = collect($period)->map(function ($date) use ($likeDailyRaw, $commentDailyRaw, $viewDailyRaw, $bookmarkDailyRaw) {
            $key = $date->toDateString();
            $likes = (int) ($likeDailyRaw[$key] ?? 0);
            $comments = (int) ($commentDailyRaw[$key] ?? 0);
            $views = (int) ($viewDailyRaw[$key] ?? 0);

            return [
                'date' => $key,
                'likes' => $likes,
                'comments' => $comments,
                'views' => $views,
                'bookmarks' => (int) ($bookmarkDailyRaw[$key] ?? 0),
                'interactions' => $likes + $comments,
            ];
        })->values();

        $likeByType = $this->aggregateByMorphType($likeQuery, 'likeable_type', self::OVERVIEW_CONTENT_TYPES);
        $commentByType = $this->aggregateCommentsByContentType($commentQuery);
        $viewByType = $this->aggregateByMorphType($viewQuery, 'viewable_type', self::OVERVIEW_CONTENT_TYPES);

        $typeComparison = collect(self::OVERVIEW_CONTENT_TYPES)->map(function ($meta, $class) use ($likeByType, $commentByType, $viewByType) {
            $short = $meta['short'];
            $likeRow = $likeByType->firstWhere('type', $short);
            $commentRow = $commentByType->firstWhere('type', $short);
            $viewRow = $viewByType->firstWhere('type', $short);
            $likes = (int) ($likeRow['count'] ?? 0);
            $comments = (int) ($commentRow['count'] ?? 0);
            $views = (int) ($viewRow['count'] ?? 0);

            return [
                'type' => $short,
                'label' => $meta['label'],
                'likes' => $likes,
                'comments' => $comments,
                'views' => $views,
                'ratios' => $this->buildInteractionRatios($likes, $comments, $views),
            ];
        })->values();

        $ratios = $this->buildInteractionRatios($likesTotal, $commentsTotal, $viewsTotal);
        $prevRatios = $this->buildInteractionRatios($prevLikes, $prevComments, $prevViews);

        $topEngagedContent = $this->buildTopEngagedContent($dateFrom, $dateTo);

        $uniqueLikeUsers = (clone $likeQuery)->distinct('user_id')->count('user_id');
        $uniqueCommentUsers = (clone $commentQuery)->distinct('user_id')->count('user_id');
        $uniqueBookmarkUsers = (clone $bookmarkQuery)->distinct('user_id')->count('user_id');

        return response()->json([
            'message' => 'Success',
            'period' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'stats' => [
                'likes_total' => $likesTotal,
                'likes_positive' => $likesPositive,
                'dislikes_total' => $dislikesTotal,
                'comments_total' => $commentsTotal,
                'views_total' => $viewsTotal,
                'bookmarks_total' => $bookmarksTotal,
                'interactions_total' => $likesTotal + $commentsTotal,
                'ratios' => $ratios,
                'ratios_comparison' => [
                    'likes_per_100_views' => $this->compareMetric((int) round($ratios['likes_per_100_views'] * 100), (int) round($prevRatios['likes_per_100_views'] * 100)),
                    'comments_per_100_views' => $this->compareMetric((int) round($ratios['comments_per_100_views'] * 100), (int) round($prevRatios['comments_per_100_views'] * 100)),
                    'likes_per_comment' => $this->compareMetric((int) round($ratios['likes_per_comment'] * 100), (int) round($prevRatios['likes_per_comment'] * 100)),
                ],
                'unique_like_users' => $uniqueLikeUsers,
                'unique_comment_users' => $uniqueCommentUsers,
                'unique_bookmark_users' => $uniqueBookmarkUsers,
                'likes_all_time' => $this->scopedLikesQuery()->count(),
                'comments_all_time' => $this->scopedCommentsQuery()->count(),
                'views_all_time' => $this->scopedViewsQuery()->count(),
                'bookmarks_all_time' => $this->scopedBookmarksQuery()->count(),
                'likes_today' => $this->scopedLikesQuery()->whereDate('created_at', today())->count(),
                'comments_today' => $this->scopedCommentsQuery()->whereDate('created_at', today())->count(),
                'views_today' => $this->scopedViewsQuery()->whereDate('created_at', today())->count(),
                'bookmarks_today' => $this->scopedBookmarksQuery()->whereDate('created_at', today())->count(),
                'trend' => [
                    'likes' => $this->compareMetric($likesTotal, $prevLikes),
                    'comments' => $this->compareMetric($commentsTotal, $prevComments),
                    'views' => $this->compareMetric($viewsTotal, $prevViews),
                    'bookmarks' => $this->compareMetric($bookmarksTotal, $prevBookmarks),
                    'interactions' => $this->compareMetric($likesTotal + $commentsTotal, $prevLikes + $prevComments),
                ],
                'volume_mix' => [
                    'labels' => ['بازدید', 'لایک', 'نظر'],
                    'values' => [$viewsTotal, $likesTotal, $commentsTotal],
                ],
                'daily_combined' => $dailyCombined,
                'type_comparison' => $typeComparison,
                'top_engaged_content' => $topEngagedContent,
                'like_ratio' => $likesTotal > 0 ? round(($likesPositive / $likesTotal) * 100, 1) : 0,
            ],
            'capabilities' => $this->contentScope()->dashboardCapabilities(),
        ], 200);
    }

    public function likeStats(Request $request)
    {
        [$dateFrom, $dateTo, $periodDays, $previousFrom, $previousTo] = $this->resolveDateRange($request);

        $periodQuery = $this->scopedLikesQuery();
        $this->applyDateRange($periodQuery, $dateFrom, $dateTo);
        $this->applyLikeTypeFilter($periodQuery, $request);
        $this->applyReactionFilter($periodQuery, $request);

        $total = (clone $periodQuery)->count();
        $likesCount = (clone $periodQuery)->where('type', 'like')->count();
        $dislikesCount = (clone $periodQuery)->where('type', 'dislike')->count();
        $uniqueUsers = (clone $periodQuery)->distinct('user_id')->count('user_id');

        $previousQuery = $this->scopedLikesQuery();
        $this->applyDateRange($previousQuery, $previousFrom, $previousTo);
        $this->applyLikeTypeFilter($previousQuery, $request);
        $this->applyReactionFilter($previousQuery, $request);

        $previousTotal = $previousQuery->count();
        $previousLikes = (clone $previousQuery)->where('type', 'like')->count();
        $previousDislikes = (clone $previousQuery)->where('type', 'dislike')->count();
        $previousUniqueUsers = (clone $previousQuery)->distinct('user_id')->count('user_id');

        $stats = $this->buildAnalyticsPayload(
            $periodQuery,
            $previousQuery,
            self::LIKE_TYPE_MAP,
            'likeable_type',
            'likeable_id',
            $dateFrom,
            $dateTo,
            $periodDays,
            $previousFrom,
            $previousTo
        );

        $stats['total'] = $total;
        $stats['likes_count'] = $likesCount;
        $stats['dislikes_count'] = $dislikesCount;
        $stats['net_score'] = $likesCount - $dislikesCount;
        $stats['unique_users'] = $uniqueUsers;
        $stats['today'] = $this->scopedLikesQuery()->whereDate('created_at', today())->count();
        $stats['week'] = $this->scopedLikesQuery()->where('created_at', '>=', now()->subDays(7))->count();
        $stats['all_time'] = $this->scopedLikesQuery()->count();
        $stats['trend'] = [
            'previous_total' => $previousTotal,
            'change' => $total - $previousTotal,
            'change_percent' => $this->changePercent($total, $previousTotal),
        ];
        $stats['comparison'] = [
            'total' => $this->compareMetric($total, $previousTotal),
            'likes' => $this->compareMetric($likesCount, $previousLikes),
            'dislikes' => $this->compareMetric($dislikesCount, $previousDislikes),
            'unique_users' => $this->compareMetric($uniqueUsers, $previousUniqueUsers),
        ];
        $stats['reaction_comparison'] = [
            'labels' => ['لایک', 'دیسلایک'],
            'current' => [$likesCount, $dislikesCount],
            'previous' => [$previousLikes, $previousDislikes],
        ];
        $stats['insights'] = $this->buildInsights($periodQuery, $total, $uniqueUsers, $likesCount, $total);

        return response()->json([
            'message' => 'Success',
            'period' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'stats' => $stats,
        ], 200);
    }

    public function userDetail(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        [$dateFrom, $dateTo] = array_slice($this->resolveDateRange($request), 0, 2);
        $user = User::query()->findOrFail($request->user_id);

        $likeQuery = $this->scopedLikesQuery()->where('user_id', $user->id);
        $this->applyDateRange($likeQuery, $dateFrom, $dateTo);
        $bookmarkQuery = $this->scopedBookmarksQuery()->where('user_id', $user->id);
        $this->applyDateRange($bookmarkQuery, $dateFrom, $dateTo);

        $likesTotal = (clone $likeQuery)->count();
        $likesPositive = (clone $likeQuery)->where('type', 'like')->count();
        $dislikesTotal = (clone $likeQuery)->where('type', 'dislike')->count();
        $bookmarksTotal = (clone $bookmarkQuery)->count();

        $likesByType = (clone $likeQuery)
            ->select('likeable_type', DB::raw('COUNT(*) as count'))
            ->groupBy('likeable_type')
            ->get()
            ->map(fn ($row) => [
                'type' => $this->resolveTypeShort($row->likeable_type, self::LIKE_TYPE_MAP),
                'label' => $this->resolveTypeLabel($row->likeable_type, self::LIKE_TYPE_MAP),
                'count' => (int) $row->count,
            ])->values();

        $bookmarksByType = (clone $bookmarkQuery)
            ->select('bookmarkable_type', DB::raw('COUNT(*) as count'))
            ->groupBy('bookmarkable_type')
            ->get()
            ->map(fn ($row) => [
                'type' => $this->resolveTypeShort($row->bookmarkable_type, self::BOOKMARK_TYPE_MAP),
                'label' => $this->resolveTypeLabel($row->bookmarkable_type, self::BOOKMARK_TYPE_MAP),
                'count' => (int) $row->count,
            ])->values();

        $recentLikes = (clone $likeQuery)->orderByDesc('created_at')->limit(5)->get()->map(function (Like $like) {
            return [
                'id' => $like->id,
                'type' => $like->type,
                'created_at' => $like->created_at,
                'content' => $this->resolveContent($like->likeable_type, $like->likeable_id, self::LIKE_TYPE_MAP),
                'content_type_label' => $this->resolveTypeLabel($like->likeable_type, self::LIKE_TYPE_MAP),
            ];
        });

        $recentBookmarks = (clone $bookmarkQuery)->orderByDesc('created_at')->limit(5)->get()->map(function (Bookmark $bookmark) {
            return [
                'id' => $bookmark->id,
                'created_at' => $bookmark->created_at,
                'content' => $this->resolveContent($bookmark->bookmarkable_type, $bookmark->bookmarkable_id, self::BOOKMARK_TYPE_MAP),
                'content_type_label' => $this->resolveTypeLabel($bookmark->bookmarkable_type, self::BOOKMARK_TYPE_MAP),
            ];
        });

        return response()->json([
            'message' => 'Success',
            'user' => $this->formatUser($user),
            'period' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'summary' => [
                'likes_total' => $likesTotal,
                'likes_positive' => $likesPositive,
                'dislikes_total' => $dislikesTotal,
                'bookmarks_total' => $bookmarksTotal,
                'total_engagement' => $likesTotal + $bookmarksTotal,
            ],
            'likes_by_type' => $likesByType,
            'bookmarks_by_type' => $bookmarksByType,
            'recent_likes' => $recentLikes,
            'recent_bookmarks' => $recentBookmarks,
        ], 200);
    }

    public function likeIndex(Request $request)
    {
        $query = $this->scopedLikesQuery()->with([
            'user:id,first_name,last_name,username,profile_pic',
        ]);

        $this->applyOptionalDateRange($query, $request);
        $this->applyLikeTypeFilter($query, $request);
        $this->applyReactionFilter($query, $request);
        $this->applyUserSearch($query, $request);
        $this->applyContentSearch($query, $request, 'likeable_type', 'likeable_id', self::LIKE_TYPE_MAP);

        $sort = $request->input('sort', 'newest');
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        $perPage = (int) $request->input('perPage', 20);
        $rows = $query->paginate($perPage);

        $items = collect($rows->items())->map(function (Like $like) {
            $content = $this->resolveContent($like->likeable_type, $like->likeable_id, self::LIKE_TYPE_MAP);

            return [
                'id' => $like->id,
                'type' => $like->type,
                'created_at' => $like->created_at,
                'content_type' => $this->resolveTypeShort($like->likeable_type, self::LIKE_TYPE_MAP),
                'content_type_label' => $this->resolveTypeLabel($like->likeable_type, self::LIKE_TYPE_MAP),
                'content_id' => $like->likeable_id,
                'content' => $content,
                'user' => $this->formatUser($like->user),
            ];
        });

        return $this->paginatedResponse($items, $rows, 'items');
    }

    public function bookmarkStats(Request $request)
    {
        [$dateFrom, $dateTo, $periodDays, $previousFrom, $previousTo] = $this->resolveDateRange($request);

        $periodQuery = $this->scopedBookmarksQuery();
        $this->applyDateRange($periodQuery, $dateFrom, $dateTo);
        $this->applyBookmarkTypeFilter($periodQuery, $request);

        $total = (clone $periodQuery)->count();
        $uniqueUsers = (clone $periodQuery)->distinct('user_id')->count('user_id');

        $previousQuery = $this->scopedBookmarksQuery();
        $this->applyDateRange($previousQuery, $previousFrom, $previousTo);
        $this->applyBookmarkTypeFilter($previousQuery, $request);

        $previousTotal = $previousQuery->count();
        $previousUniqueUsers = (clone $previousQuery)->distinct('user_id')->count('user_id');

        $stats = $this->buildAnalyticsPayload(
            $periodQuery,
            $previousQuery,
            self::BOOKMARK_TYPE_MAP,
            'bookmarkable_type',
            'bookmarkable_id',
            $dateFrom,
            $dateTo,
            $periodDays,
            $previousFrom,
            $previousTo
        );

        $stats['total'] = $total;
        $stats['unique_users'] = $uniqueUsers;
        $stats['today'] = $this->scopedBookmarksQuery()->whereDate('created_at', today())->count();
        $stats['week'] = $this->scopedBookmarksQuery()->where('created_at', '>=', now()->subDays(7))->count();
        $stats['all_time'] = $this->scopedBookmarksQuery()->count();
        $stats['trend'] = [
            'previous_total' => $previousTotal,
            'change' => $total - $previousTotal,
            'change_percent' => $this->changePercent($total, $previousTotal),
        ];
        $stats['comparison'] = [
            'total' => $this->compareMetric($total, $previousTotal),
            'unique_users' => $this->compareMetric($uniqueUsers, $previousUniqueUsers),
        ];
        $stats['insights'] = $this->buildInsights($periodQuery, $total, $uniqueUsers);

        return response()->json([
            'message' => 'Success',
            'period' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'stats' => $stats,
        ], 200);
    }

    public function bookmarkIndex(Request $request)
    {
        $query = $this->scopedBookmarksQuery()->with([
            'user:id,first_name,last_name,username,profile_pic',
        ]);

        $this->applyOptionalDateRange($query, $request);
        $this->applyBookmarkTypeFilter($query, $request);
        $this->applyUserSearch($query, $request);
        $this->applyContentSearch($query, $request, 'bookmarkable_type', 'bookmarkable_id', self::BOOKMARK_TYPE_MAP);

        $sort = $request->input('sort', 'newest');
        $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

        $perPage = (int) $request->input('perPage', 20);
        $rows = $query->paginate($perPage);

        $items = collect($rows->items())->map(function (Bookmark $bookmark) {
            $content = $this->resolveContent($bookmark->bookmarkable_type, $bookmark->bookmarkable_id, self::BOOKMARK_TYPE_MAP);

            return [
                'id' => $bookmark->id,
                'created_at' => $bookmark->created_at,
                'content_type' => $this->resolveTypeShort($bookmark->bookmarkable_type, self::BOOKMARK_TYPE_MAP),
                'content_type_label' => $this->resolveTypeLabel($bookmark->bookmarkable_type, self::BOOKMARK_TYPE_MAP),
                'content_id' => $bookmark->bookmarkable_id,
                'content' => $content,
                'user' => $this->formatUser($bookmark->user),
            ];
        });

        return $this->paginatedResponse($items, $rows, 'items');
    }

    private function buildAnalyticsPayload(
        Builder $periodQuery,
        Builder $previousQuery,
        array $typeMap,
        string $typeColumn,
        string $idColumn,
        string $dateFrom,
        string $dateTo,
        int $periodDays,
        string $previousFrom,
        string $previousTo
    ): array {
        $dailyRaw = (clone $periodQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $period = CarbonPeriod::create($dateFrom, $dateTo);
        $daily = collect($period)->map(function ($date) use ($dailyRaw) {
            $key = $date->toDateString();

            return ['date' => $key, 'count' => (int) ($dailyRaw[$key] ?? 0)];
        })->values();

        $byType = (clone $periodQuery)
            ->select($typeColumn, DB::raw('COUNT(*) as count'))
            ->groupBy($typeColumn)
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'type' => $this->resolveTypeShort($row->{$typeColumn}, $typeMap),
                'label' => $this->resolveTypeLabel($row->{$typeColumn}, $typeMap),
                'count' => (int) $row->count,
            ])
            ->values();

        $topContent = (clone $periodQuery)
            ->select($typeColumn, $idColumn, DB::raw('COUNT(*) as items_count'))
            ->groupBy($typeColumn, $idColumn)
            ->orderByDesc('items_count')
            ->limit(15)
            ->get()
            ->map(fn ($row) => $this->formatTopContentItem($row, $typeMap, $typeColumn, $idColumn))
            ->filter()
            ->values();

        $hourlyRaw = (clone $periodQuery)
            ->select(DB::raw(SqlDialect::hour('created_at').' as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour');

        $hourly = collect(range(0, 23))->map(fn ($hour) => [
            'hour' => $hour,
            'label' => sprintf('%02d:00', $hour),
            'count' => (int) ($hourlyRaw[$hour] ?? 0),
        ])->values();

        $previousDailyRaw = (clone $previousQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $previousPeriod = CarbonPeriod::create($previousFrom, $previousTo);
        $previousDaily = collect($previousPeriod)->map(function ($date) use ($previousDailyRaw) {
            $key = $date->toDateString();

            return (int) ($previousDailyRaw[$key] ?? 0);
        })->values();

        $weekdayLabels = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
        $weekdayRaw = (clone $periodQuery)
            ->select(DB::raw(SqlDialect::dayOfWeek('created_at').' as weekday'), DB::raw('COUNT(*) as count'))
            ->groupBy('weekday')
            ->pluck('count', 'weekday');

        $byWeekday = collect(range(1, 7))->map(fn ($day) => [
            'weekday' => $day,
            'label' => $weekdayLabels[$day - 1] ?? (string) $day,
            'count' => (int) ($weekdayRaw[$day] ?? 0),
        ])->values();

        $topUsers = (clone $periodQuery)
            ->select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $userIds = $topUsers->pluck('user_id')->filter()->all();
        $usersMap = User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $topUsersList = $topUsers->map(function ($row) use ($usersMap) {
            $user = $usersMap->get($row->user_id);

            return [
                'user' => $user ? $this->formatUser($user) : null,
                'count' => (int) $row->count,
            ];
        })->values();

        $heatmap = $this->buildHeatmap($periodQuery);
        $monthly = $this->buildMonthlySeries($periodQuery, $dateFrom, $dateTo);
        $dailyUniqueUsers = (clone $periodQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(DISTINCT user_id) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $dailyUnique = collect($period)->map(function ($date) use ($dailyUniqueUsers) {
            $key = $date->toDateString();

            return ['date' => $key, 'count' => (int) ($dailyUniqueUsers[$key] ?? 0)];
        })->values();

        return [
            'daily' => $daily,
            'by_type' => $byType,
            'top_content' => $topContent,
            'hourly' => $hourly,
            'by_weekday' => $byWeekday,
            'top_users' => $topUsersList,
            'heatmap' => $heatmap,
            'monthly' => $monthly,
            'daily_unique_users' => $dailyUnique,
            'daily_comparison' => [
                'labels' => collect(range(1, $periodDays))->map(fn ($i) => 'روز ' . $i)->values(),
                'current' => $daily->pluck('count')->values(),
                'previous' => $previousDaily->pad($periodDays, 0)->take($periodDays)->values(),
            ],
            'by_type_comparison' => $this->buildTypeComparison($periodQuery, $previousQuery, $typeMap, $typeColumn),
        ];
    }

    private function resolveDateRange(Request $request): array
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $periodDays = $this->calculatePeriodDays($dateFrom, $dateTo);
        $previousFrom = Carbon::parse($dateFrom)->subDays($periodDays)->toDateString();
        $previousTo = Carbon::parse($dateFrom)->subDay()->toDateString();

        return [$dateFrom, $dateTo, $periodDays, $previousFrom, $previousTo];
    }

    private function applyOptionalDateRange(Builder $query, Request $request): void
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
    }

    private function applyDateRange(Builder $query, string $dateFrom, string $dateTo): void
    {
        $query->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);
    }

    private function calculatePeriodDays(string $dateFrom, string $dateTo): int
    {
        $start = Carbon::parse($dateFrom)->startOfDay();
        $end = Carbon::parse($dateTo)->startOfDay();

        return max(1, (int) $start->diffInDays($end) + 1);
    }

    private function applyLikeTypeFilter(Builder $query, Request $request): void
    {
        $this->applyMorphTypeFilter($query, $request, 'content_type', 'likeable_type', self::LIKE_TYPE_MAP);
    }

    private function applyBookmarkTypeFilter(Builder $query, Request $request): void
    {
        $this->applyMorphTypeFilter($query, $request, 'content_type', 'bookmarkable_type', self::BOOKMARK_TYPE_MAP);
    }

    private function applyMorphTypeFilter(Builder $query, Request $request, string $param, string $column, array $typeMap): void
    {
        if (!$request->filled($param) || $request->input($param) === 'all') {
            return;
        }

        $type = $request->input($param);
        $class = $this->resolveTypeClass($type, $typeMap);

        if ($class) {
            $query->where(function ($q) use ($class, $type, $column) {
                $q->where($column, $class)
                    ->orWhere($column, 'like', '%\\' . $type);
            });
        }
    }

    private function applyReactionFilter(Builder $query, Request $request): void
    {
        $reaction = $request->input('reaction_type');

        if ($reaction === 'like') {
            $query->where('type', 'like');
        } elseif ($reaction === 'dislike') {
            $query->where('type', 'dislike');
        }
    }

    private function applyUserSearch(Builder $query, Request $request): void
    {
        if (!$request->filled('search')) {
            return;
        }

        $search = $request->search;
        $query->whereHas('user', function ($userQuery) use ($search) {
            $userQuery->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
        });
    }

    private function buildTypeComparison(Builder $currentQuery, Builder $previousQuery, array $typeMap, string $typeColumn): array
    {
        $labels = [];
        $current = [];
        $previous = [];

        foreach ($typeMap as $class => $meta) {
            $labels[] = $meta['label'];
            $current[] = (clone $currentQuery)->where(function ($q) use ($class, $meta, $typeColumn) {
                $q->where($typeColumn, $class)
                    ->orWhere($typeColumn, 'like', '%\\' . $meta['short']);
            })->count();
            $previous[] = (clone $previousQuery)->where(function ($q) use ($class, $meta, $typeColumn) {
                $q->where($typeColumn, $class)
                    ->orWhere($typeColumn, 'like', '%\\' . $meta['short']);
            })->count();
        }

        return [
            'labels' => $labels,
            'current' => $current,
            'previous' => $previous,
        ];
    }

    private function compareMetric(int $current, int $previous): array
    {
        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $current - $previous,
            'change_percent' => $this->changePercent($current, $previous),
        ];
    }

    private function changePercent(int $current, int $previous): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }

        return $current > 0 ? 100 : 0;
    }

    private function resolveTypeClass(string $type, array $typeMap): ?string
    {
        foreach ($typeMap as $class => $meta) {
            if ($meta['short'] === $type || class_basename($class) === $type) {
                return $class;
            }
        }

        return null;
    }

    private function resolveTypeShort(?string $type, array $typeMap): string
    {
        if (!$type) {
            return 'Unknown';
        }

        foreach ($typeMap as $class => $meta) {
            if ($type === $class || str_ends_with($type, '\\' . $meta['short'])) {
                return $meta['short'];
            }
        }

        return class_basename($type);
    }

    private function resolveTypeLabel(?string $type, array $typeMap): string
    {
        if (!$type) {
            return 'نامشخص';
        }

        foreach ($typeMap as $class => $meta) {
            if ($type === $class || str_ends_with($type, '\\' . $meta['short'])) {
                return $meta['label'];
            }
        }

        return class_basename($type);
    }

    private function resolveContent(?string $type, ?int $id, array $typeMap): ?array
    {
        if (!$type || !$id) {
            return null;
        }

        $class = null;
        $meta = null;

        foreach ($typeMap as $modelClass => $modelMeta) {
            if ($type === $modelClass || str_ends_with($type, '\\' . $modelMeta['short'])) {
                $class = $modelClass;
                $meta = $modelMeta;
                break;
            }
        }

        if (!$class) {
            return null;
        }

        $model = $class::query()->find($id);

        if (!$model) {
            return [
                'title' => '(حذف شده)',
                'slug' => null,
                'deleted' => true,
            ];
        }

        return [
            'title' => $this->formatContentTitle($model, $class, $meta),
            'slug' => $meta['slug_field'] ? ($model->{$meta['slug_field']} ?? null) : null,
            'deleted' => false,
        ];
    }

    private function formatContentTitle($model, string $class, array $meta): string
    {
        $field = $meta['title_field'] ?? null;

        if (!$field || !isset($model->{$field})) {
            return '(بدون عنوان)';
        }

        $title = strip_tags((string) $model->{$field});

        if (in_array($class, [Comment::class, Answer::class], true)) {
            $title = preg_replace('/\s+/', ' ', trim($title)) ?: '(بدون متن)';
        }

        return mb_strlen($title) > 80 ? mb_substr($title, 0, 80) . '…' : $title;
    }

    private function formatTopContentItem($row, array $typeMap, string $typeColumn, string $idColumn): ?array
    {
        $content = $this->resolveContent($row->{$typeColumn}, $row->{$idColumn}, $typeMap);

        if (!$content) {
            return null;
        }

        $title = $content['title'];
        $shortTitle = mb_strlen($title) > 40 ? mb_substr($title, 0, 40) . '…' : $title;

        return [
            'content_type' => $this->resolveTypeShort($row->{$typeColumn}, $typeMap),
            'content_type_label' => $this->resolveTypeLabel($row->{$typeColumn}, $typeMap),
            'content_id' => $row->{$idColumn},
            'title' => $content['title'],
            'short_title' => $shortTitle,
            'slug' => $content['slug'],
            'deleted' => $content['deleted'],
            'items_count' => (int) $row->items_count,
        ];
    }

    private function formatUser(?User $user): ?array
    {
        if (!$user || !$user->id) {
            return null;
        }

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'profile_pic' => $user->profile_pic,
        ];
    }

    private function paginatedResponse($items, $paginator, string $key)
    {
        return response()->json([
            'message' => 'Success',
            $key => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ], 200);
    }

    private function aggregateByType(Builder $query, array $typeMap, string $typeColumn)
    {
        return (clone $query)
            ->select($typeColumn, DB::raw('COUNT(*) as count'))
            ->groupBy($typeColumn)
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'type' => $this->resolveTypeShort($row->{$typeColumn}, $typeMap),
                'label' => $this->resolveTypeLabel($row->{$typeColumn}, $typeMap),
                'count' => (int) $row->count,
            ])
            ->values();
    }

    private function buildTopEngagedContent(string $dateFrom, string $dateTo): array
    {
        $results = [];

        foreach (self::OVERVIEW_CONTENT_TYPES as $class => $meta) {
            $likeRows = $this->scopedLikesQuery()
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->where(function ($q) use ($class, $meta) {
                    $q->where('likeable_type', $class)
                        ->orWhere('likeable_type', 'like', '%\\' . $meta['short']);
                })
                ->select('likeable_id', DB::raw('COUNT(*) as count'))
                ->groupBy('likeable_id')
                ->pluck('count', 'likeable_id');

            $viewRows = $this->scopedViewsQuery()
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->where(function ($q) use ($class, $meta) {
                    $q->where('viewable_type', $class)
                        ->orWhere('viewable_type', 'like', '%\\' . $meta['short']);
                })
                ->select('viewable_id', DB::raw('COUNT(*) as count'))
                ->groupBy('viewable_id')
                ->pluck('count', 'viewable_id');

            $commentRows = $this->scopedCommentsQuery()
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->where(function ($q) use ($class, $meta) {
                    $q->where('commentable_type', $class)
                        ->orWhere('commentable_type', 'like', '%\\' . $meta['short']);
                })
                ->select('commentable_id', DB::raw('COUNT(*) as count'))
                ->groupBy('commentable_id')
                ->pluck('count', 'commentable_id');

            $ids = collect($likeRows->keys())->merge($viewRows->keys())->merge($commentRows->keys())->unique();

            foreach ($ids as $id) {
                $likes = (int) ($likeRows[$id] ?? 0);
                $views = (int) ($viewRows[$id] ?? 0);
                $comments = (int) ($commentRows[$id] ?? 0);

                if ($likes + $views + $comments <= 0) {
                    continue;
                }

                $content = $this->resolveContent($class, (int) $id, self::LIKE_TYPE_MAP);
                if (!$content) {
                    continue;
                }

                $ratios = $this->buildInteractionRatios($likes, $comments, $views);

                $results[] = [
                    'content_type' => $meta['short'],
                    'content_type_label' => $meta['label'],
                    'content_id' => (int) $id,
                    'title' => $content['title'],
                    'short_title' => mb_strlen($content['title']) > 40 ? mb_substr($content['title'], 0, 40) . '…' : $content['title'],
                    'likes_count' => $likes,
                    'comments_count' => $comments,
                    'views_count' => $views,
                    'ratios' => $ratios,
                    'engagement_score' => round($ratios['interaction_per_100_views'], 2),
                ];
            }
        }

        return collect($results)
            ->sortByDesc('engagement_score')
            ->values()
            ->take(15)
            ->all();
    }

    private function buildInteractionRatios(int $likes, int $comments, int $views): array
    {
        return [
            'likes_per_100_views' => $views > 0 ? round(($likes / $views) * 100, 2) : 0,
            'comments_per_100_views' => $views > 0 ? round(($comments / $views) * 100, 2) : 0,
            'interaction_per_100_views' => $views > 0 ? round((($likes + $comments) / $views) * 100, 2) : 0,
            'likes_per_comment' => $comments > 0 ? round($likes / $comments, 2) : ($likes > 0 ? (float) $likes : 0),
            'comments_per_like' => $likes > 0 ? round($comments / $likes, 2) : ($comments > 0 ? (float) $comments : 0),
        ];
    }

    private function aggregateByMorphType(Builder $query, string $typeColumn, array $typeMap)
    {
        $rows = (clone $query)
            ->select($typeColumn, DB::raw('COUNT(*) as count'))
            ->groupBy($typeColumn)
            ->get();

        $grouped = [];
        foreach ($typeMap as $class => $meta) {
            $grouped[$meta['short']] = [
                'type' => $meta['short'],
                'label' => $meta['label'],
                'count' => 0,
            ];
        }

        foreach ($rows as $row) {
            $short = $this->resolveTypeShort($row->{$typeColumn}, $typeMap);
            if (!isset($grouped[$short])) {
                continue;
            }
            $grouped[$short]['count'] += (int) $row->count;
        }

        return collect($grouped)->values()->sortByDesc('count')->values();
    }

    private function aggregateCommentsByContentType(Builder $query)
    {
        $rows = (clone $query)
            ->select('commentable_type', DB::raw('COUNT(*) as count'))
            ->groupBy('commentable_type')
            ->get();

        $grouped = [];
        foreach (self::OVERVIEW_CONTENT_TYPES as $class => $meta) {
            $grouped[$meta['short']] = [
                'type' => $meta['short'],
                'label' => $meta['label'],
                'count' => 0,
            ];
        }

        foreach ($rows as $row) {
            $short = $this->resolveTypeShort($row->commentable_type, self::OVERVIEW_CONTENT_TYPES);
            if (!isset($grouped[$short])) {
                continue;
            }
            $grouped[$short]['count'] += (int) $row->count;
        }

        return collect($grouped)->values()->sortByDesc('count')->values();
    }

    private function buildInsights(Builder $periodQuery, int $total, int $uniqueUsers, ?int $positiveCount = null, ?int $totalForRatio = null): array
    {
        $avgPerUser = $uniqueUsers > 0 ? round($total / $uniqueUsers, 1) : 0;

        $peakHourRow = (clone $periodQuery)
            ->select(DB::raw(SqlDialect::hour('created_at').' as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderByDesc('count')
            ->first();

        $weekdayLabels = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
        $peakDayRow = (clone $periodQuery)
            ->select(DB::raw(SqlDialect::dayOfWeek('created_at').' as weekday'), DB::raw('COUNT(*) as count'))
            ->groupBy('weekday')
            ->orderByDesc('count')
            ->first();

        $peakDay = $peakDayRow
            ? ($weekdayLabels[(int) $peakDayRow->weekday - 1] ?? '—')
            : '—';

        $insights = [
            'avg_per_user' => $avgPerUser,
            'peak_hour' => $peakHourRow ? sprintf('%02d:00', (int) $peakHourRow->hour) : '—',
            'peak_hour_count' => $peakHourRow ? (int) $peakHourRow->count : 0,
            'peak_day' => $peakDay,
            'peak_day_count' => $peakDayRow ? (int) $peakDayRow->count : 0,
        ];

        if ($positiveCount !== null && $totalForRatio !== null && $totalForRatio > 0) {
            $insights['positive_ratio'] = round(($positiveCount / $totalForRatio) * 100, 1);
        }

        return $insights;
    }

    private function buildHeatmap(Builder $periodQuery): array
    {
        $weekdayLabels = ['یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه'];
        $raw = (clone $periodQuery)
            ->select(
                DB::raw(SqlDialect::dayOfWeek('created_at').' as weekday'),
                DB::raw(SqlDialect::hour('created_at').' as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('weekday', 'hour')
            ->get();

        $matrix = [];
        $max = 0;

        foreach (range(1, 7) as $day) {
            foreach (range(0, 23) as $hour) {
                $count = (int) ($raw->first(fn ($r) => (int) $r->weekday === $day && (int) $r->hour === $hour)?->count ?? 0);
                $max = max($max, $count);
                $matrix[] = [
                    'weekday' => $day,
                    'weekday_label' => $weekdayLabels[$day - 1],
                    'hour' => $hour,
                    'hour_label' => sprintf('%02d:00', $hour),
                    'count' => $count,
                ];
            }
        }

        return [
            'cells' => $matrix,
            'max' => $max,
            'weekday_labels' => $weekdayLabels,
        ];
    }

    private function buildMonthlySeries(Builder $periodQuery, string $dateFrom, string $dateTo): array
    {
        if ($this->calculatePeriodDays($dateFrom, $dateTo) < 32) {
            return [];
        }

        $raw = (clone $periodQuery)
            ->select(DB::raw(SqlDialect::yearMonth('created_at').' as month'), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        return collect($raw)->map(fn ($count, $month) => [
            'month' => $month,
            'label' => $month,
            'count' => (int) $count,
        ])->values()->all();
    }

    private function applyContentSearch(Builder $query, Request $request, string $typeColumn, string $idColumn, array $typeMap): void
    {
        if (!$request->filled('content_search')) {
            return;
        }

        $search = $request->content_search;
        $matchingIds = [];

        foreach ($typeMap as $class => $meta) {
            $field = $meta['title_field'] ?? null;
            if (!$field) {
                continue;
            }

            $ids = $class::query()
                ->where($field, 'like', "%{$search}%")
                ->pluck('id')
                ->all();

            foreach ($ids as $id) {
                $matchingIds[] = ['type' => $class, 'id' => $id];
            }
        }

        if (empty($matchingIds)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($q) use ($matchingIds, $typeColumn, $idColumn) {
            foreach ($matchingIds as $match) {
                $q->orWhere(function ($sub) use ($match, $typeColumn, $idColumn) {
                    $sub->where($typeColumn, $match['type'])
                        ->where($idColumn, $match['id']);
                });
            }
        });
    }
}
