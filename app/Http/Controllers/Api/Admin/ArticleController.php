<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Rating;
use App\Models\User;
use App\Models\View;
use App\Services\UploadTokenService;
use App\Support\SqlDialect;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use LaravelInteraction\Bookmark\Bookmark;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 15);
        $search = $request->input('search', '');
        $categoryId = $request->input('category_id');
        $publish = $request->input('publish');
        $status = $request->input('status');
        $isFeatured = $request->input('is_featured');
        $trashed = $request->boolean('trashed');

        $query = Article::with([
            'user:id,first_name,last_name,username,profile_pic',
            'category:id,title,english_title,slug',
        ])->withCount([
            'likes as likes_count' => fn ($q) => $q->where('type', 'like'),
            'bookmarkers as bookmarks_count',
            'comments as comments_count',
            'views as views_count',
            'ratings as ratings_count',
        ])->withAvg('ratings as average_rating', 'rating');

        if ($trashed) {
            $query->onlyTrashed();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($publish !== null && $publish !== '') {
            $query->where('publish', filter_var($publish, FILTER_VALIDATE_BOOLEAN));
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($isFeatured !== null && $isFeatured !== '') {
            $query->where('is_featured', filter_var($isFeatured, FILTER_VALIDATE_BOOLEAN));
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'author' => $query
                ->leftJoin('users as article_authors', 'article_authors.id', '=', 'articles.user_id')
                ->orderBy('article_authors.first_name')
                ->orderBy('article_authors.last_name')
                ->orderByDesc('articles.created_at')
                ->orderByDesc('articles.id')
                ->select('articles.*'),
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'updated' => $query->orderByDesc('updated_at')->orderByDesc('id'),
            'most_views' => $query->orderByDesc('views_count')->orderByDesc('id'),
            'most_likes' => $query->orderByDesc('likes_count')->orderByDesc('id'),
            'most_bookmarks' => $query->orderByDesc('bookmarks_count')->orderByDesc('id'),
            'most_comments' => $query->orderByDesc('comments_count')->orderByDesc('id'),
            'most_rating' => $query
                ->orderByDesc(
                    Rating::query()
                        ->selectRaw('coalesce(avg(rating), 0)')
                        ->whereColumn('rateable_id', 'articles.id')
                        ->where('rateable_type', Article::class)
                )
                ->orderByDesc(
                    Rating::query()
                        ->selectRaw('count(*)')
                        ->whereColumn('rateable_id', 'articles.id')
                        ->where('rateable_type', Article::class)
                )
                ->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $articles = $query->paginate($perPage);

        $articles->getCollection()->transform(function (Article $article) {
            $views = $article->viewCount();

            return [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'cover_image' => $article->cover_image,
                'reading_time_minutes' => $article->reading_time_minutes,
                'views_count' => $views,
                'likes_count' => $article->likes_count,
                'bookmarks_count' => $article->bookmarks_count,
                'comments_count' => $article->comments_count,
                'average_rating' => round((float) ($article->average_rating ?? 0), 2),
                'ratings_count' => (int) ($article->ratings_count ?? 0),
                'publish' => $article->publish,
                'status' => $article->status,
                'is_featured' => $article->is_featured,
                'scheduled_at' => $article->scheduled_at,
                'published_at' => $article->published_at,
                'created_at' => $article->created_at,
                'updated_at' => $article->updated_at,
                'deleted_at' => $article->deleted_at,
                'user' => $article->user,
                'category' => $article->category,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'articles' => $articles,
        ]);
    }

    public function show(Article $article)
    {
        $article->load([
            'user:id,first_name,last_name,username,profile_pic',
            'category:id,title,english_title,slug',
        ]);
        $article->loadCount(['likes as likes_count', 'bookmarkers as bookmarks_count', 'comments', 'views']);

        $tags = $article->tags->map(fn ($tag) => [
            'id' => $tag->tag_id,
            'name' => $tag->name,
            'normalized' => $tag->normalized,
        ]);

        return response()->json([
            'message' => 'Success',
            'article' => array_merge($article->toArray(), [
                'tags' => $tags,
                'likes_count' => $article->likes_count,
                'bookmarks_count' => $article->bookmarks_count,
                'comments_count' => $article->comments_count,
                'views_count' => $article->viewCount(),
            ]),
        ]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:article_categories,id',
            'title' => 'required|string|min:5|max:255',
            'english_title' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string|min:50',
            'cover_image' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:500',
            'og_image' => 'nullable|string|max:500',
            'publish' => 'nullable|boolean',
            'status' => 'nullable|in:draft,pending,published,archived',
            'is_featured' => 'nullable|boolean',
            'scheduled_at' => 'nullable|date',
            'reading_time_minutes' => 'nullable|integer|min:1|max:999',
            'tags' => 'nullable|array|max:5',
            'tags.*' => 'string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $publish = (bool) ($request->publish ?? false);
        $status = $request->status ?? ($publish ? 'published' : 'draft');

        $article = Article::create([
            'user_id' => $request->user_id,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'english_title' => $request->english_title,
            'excerpt' => $request->excerpt,
            'content' => $request->content,
            'cover_image' => $request->cover_image,
            'meta_keywords' => $request->meta_keywords,
            'seo_title' => $request->seo_title,
            'seo_description' => $request->seo_description,
            'canonical_url' => $request->canonical_url,
            'og_image' => $request->og_image,
            'publish' => $publish,
            'status' => $status,
            'is_featured' => (bool) ($request->is_featured ?? false),
            'scheduled_at' => $request->scheduled_at,
            'reading_time_minutes' => $request->filled('reading_time_minutes')
                ? (int) $request->reading_time_minutes
                : null,
            'published_at' => $publish ? now() : null,
        ]);

        if ($request->tags) {
            $article->tag($request->tags);
        }

        $article->load(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,english_title']);

        return response()->json(['message' => 'Article created successfully', 'article' => $article], 201);
    }

    public function update(Request $request, Article $article)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'category_id' => 'sometimes|exists:article_categories,id',
            'title' => 'sometimes|string|min:5|max:255',
            'english_title' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'sometimes|string|min:50',
            'cover_image' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:500',
            'og_image' => 'nullable|string|max:500',
            'publish' => 'nullable|boolean',
            'status' => 'nullable|in:draft,pending,published,archived',
            'is_featured' => 'nullable|boolean',
            'scheduled_at' => 'nullable|date',
            'reading_time_minutes' => 'nullable|integer|min:1|max:999',
            'tags' => 'nullable|array|max:5',
            'tags.*' => 'string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $fields = [
            'user_id', 'category_id', 'title', 'english_title', 'excerpt', 'content', 'cover_image',
            'meta_keywords', 'seo_title', 'seo_description', 'canonical_url', 'og_image',
            'publish', 'status', 'is_featured', 'scheduled_at', 'reading_time_minutes',
        ];

        $updateData = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $updateData[$field] = $field === 'reading_time_minutes'
                    ? ($request->filled('reading_time_minutes') ? (int) $request->reading_time_minutes : null)
                    : $request->input($field);
            }
        }

        if ($request->has('publish') && $request->publish && ! $article->published_at) {
            $updateData['published_at'] = now();
        }

        $article->update($updateData);

        if ($request->has('tags')) {
            $request->tags ? $article->retag($request->tags) : $article->detag();
        }

        $article->load(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,english_title']);

        return response()->json(['message' => 'Article updated successfully', 'article' => $article]);
    }

    public function delete(Article $article)
    {
        $article->delete();

        return response()->json(['message' => 'Article moved to trash']);
    }

    public function restore(int $id)
    {
        $article = Article::onlyTrashed()->findOrFail($id);
        $article->restore();

        return response()->json(['message' => 'Article restored', 'article' => $article]);
    }

    public function forceDelete(int $id)
    {
        $article = Article::onlyTrashed()->findOrFail($id);
        $article->detag();
        $article->forceDelete();

        return response()->json(['message' => 'Article permanently deleted']);
    }

    public function togglePublish(Article $article)
    {
        $article->publish = ! $article->publish;
        $article->status = $article->publish ? 'published' : 'draft';
        if ($article->publish && ! $article->published_at) {
            $article->published_at = now();
        }
        $article->save();

        return response()->json([
            'message' => 'Publish status updated',
            'publish' => $article->publish,
            'status' => $article->status,
        ]);
    }

    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:articles,id',
            'action' => 'required|in:publish,unpublish,delete,restore,archive,feature,unfeature,force_delete',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $ids = $request->ids;
        $action = $request->action;
        $count = 0;

        match ($action) {
            'publish' => $count = Article::whereIn('id', $ids)->update([
                'publish' => true,
                'status' => 'published',
                'published_at' => now(),
            ]),
            'unpublish' => $count = Article::whereIn('id', $ids)->update(['publish' => false, 'status' => 'draft']),
            'archive' => $count = Article::whereIn('id', $ids)->update(['publish' => false, 'status' => 'archived']),
            'feature' => $count = Article::whereIn('id', $ids)->update(['is_featured' => true]),
            'unfeature' => $count = Article::whereIn('id', $ids)->update(['is_featured' => false]),
            'delete' => $count = Article::whereIn('id', $ids)->delete(),
            'restore' => $count = Article::onlyTrashed()->whereIn('id', $ids)->restore(),
            'force_delete' => $count = tap(Article::onlyTrashed()->whereIn('id', $ids)->get(), function ($articles) {
                foreach ($articles as $article) {
                    $article->detag();
                    $article->forceDelete();
                }
            })->count(),
            default => null,
        };

        return response()->json(['message' => 'Bulk action completed', 'affected' => $count]);
    }

    public function stats()
    {
        $articleMorph = fn ($q, string $col) => $q->where(function ($inner) use ($col) {
            $inner->where($col, Article::class)->orWhere($col, 'like', '%\\Article');
        });

        $totalLikes = Like::query()->where(fn ($q) => $articleMorph($q, 'likeable_type'))->where('type', 'like')->count();
        $totalBookmarks = Bookmark::query()->where(fn ($q) => $articleMorph($q, 'bookmarkable_type'))->count();
        $totalComments = Comment::query()->where(fn ($q) => $articleMorph($q, 'commentable_type'))->count();

        return response()->json([
            'message' => 'Success',
            'stats' => [
                'total' => Article::withTrashed()->count(),
                'published' => Article::where('publish', true)->where('status', 'published')->count(),
                'drafts' => Article::where('status', 'draft')->count(),
                'pending' => Article::where('status', 'pending')->count(),
                'archived' => Article::where('status', 'archived')->count(),
                'trash' => Article::onlyTrashed()->count(),
                'featured' => Article::where('is_featured', true)->where('publish', true)->count(),
                'total_views' => (int) View::query()->where(fn ($q) => $articleMorph($q, 'viewable_type'))->count(),
                'total_likes' => $totalLikes,
                'total_bookmarks' => $totalBookmarks,
                'total_comments' => $totalComments,
                'avg_reading_time' => round((float) Article::avg('reading_time_minutes'), 1),
                'new_this_month' => Article::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }

    public function analytics(Request $request)
    {
        [$dateFrom, $dateTo, $periodDays, $previousFrom, $previousTo] = $this->resolveAnalyticsDateRange($request);

        $articleMorph = fn ($q, string $col) => $q->where(function ($inner) use ($col) {
            $inner->where($col, Article::class)->orWhere($col, 'like', '%\\Article');
        });

        $period = CarbonPeriod::create($dateFrom, $dateTo);

        $viewsDaily = View::query()
            ->where(fn ($q) => $articleMorph($q, 'viewable_type'))
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(DB::raw(SqlDialect::date('created_at').' as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $likesDaily = Like::query()
            ->where(fn ($q) => $articleMorph($q, 'likeable_type'))
            ->where('type', 'like')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(DB::raw(SqlDialect::date('created_at').' as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $commentsDaily = Comment::query()
            ->where(fn ($q) => $articleMorph($q, 'commentable_type'))
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(DB::raw(SqlDialect::date('created_at').' as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $bookmarksDaily = Bookmark::query()
            ->where(fn ($q) => $articleMorph($q, 'bookmarkable_type'))
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(DB::raw(SqlDialect::date('created_at').' as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $articlesDaily = Article::query()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(DB::raw(SqlDialect::date('created_at').' as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $dailyTrend = collect($period)->map(function ($date) use ($viewsDaily, $likesDaily, $commentsDaily, $bookmarksDaily, $articlesDaily) {
            $key = $date->toDateString();

            return [
                'date' => $key,
                'views' => (int) ($viewsDaily[$key] ?? 0),
                'likes' => (int) ($likesDaily[$key] ?? 0),
                'comments' => (int) ($commentsDaily[$key] ?? 0),
                'bookmarks' => (int) ($bookmarksDaily[$key] ?? 0),
                'new_articles' => (int) ($articlesDaily[$key] ?? 0),
            ];
        })->values();

        $periodViews = (int) $dailyTrend->sum('views');
        $periodLikes = (int) $dailyTrend->sum('likes');
        $periodComments = (int) $dailyTrend->sum('comments');
        $periodBookmarks = (int) $dailyTrend->sum('bookmarks');

        $previousViews = View::query()
            ->where(fn ($q) => $articleMorph($q, 'viewable_type'))
            ->whereDate('created_at', '>=', $previousFrom)
            ->whereDate('created_at', '<=', $previousTo)
            ->count();

        $previousLikes = Like::query()
            ->where(fn ($q) => $articleMorph($q, 'likeable_type'))
            ->where('type', 'like')
            ->whereDate('created_at', '>=', $previousFrom)
            ->whereDate('created_at', '<=', $previousTo)
            ->count();

        $statusDistribution = [
            'labels' => ['منتشر شده', 'پیش‌نویس', 'در انتظار', 'بایگانی', 'سطل زباله'],
            'data' => [
                Article::where('publish', true)->where('status', 'published')->count(),
                Article::where('status', 'draft')->count(),
                Article::where('status', 'pending')->count(),
                Article::where('status', 'archived')->count(),
                Article::onlyTrashed()->count(),
            ],
            'colors' => ['#34d399', '#9ca3af', '#fbbf24', '#64748b', '#f87171'],
        ];

        $categoryDistribution = ArticleCategory::query()
            ->withCount(['articles' => fn ($q) => $q->where('publish', true)])
            ->orderByDesc('articles_count')
            ->limit(10)
            ->get()
            ->pipe(fn ($cats) => [
                'labels' => $cats->pluck('title')->all(),
                'data' => $cats->pluck('articles_count')->map(fn ($c) => (int) $c)->all(),
                'colors' => ['#fbbf24', '#60a5fa', '#34d399', '#a78bfa', '#f472b6', '#fb923c', '#22d3ee', '#818cf8', '#4ade80', '#e879f9'],
            ]);

        $engagementMix = [
            'labels' => ['بازدید', 'لایک', 'نظر', 'بوکمارک'],
            'data' => [$periodViews, $periodLikes, $periodComments, $periodBookmarks],
            'colors' => ['#60a5fa', '#f472b6', '#22d3ee', '#a78bfa'],
        ];

        $formatTopArticles = function (string $orderColumn, int $limit = 10) {
            $query = Article::query()
                ->with(['category:id,title', 'user:id,first_name,last_name,username'])
                ->withCount([
                    'likes as likes_count' => fn ($q) => $q->where('type', 'like'),
                    'bookmarkers as bookmarks_count',
                    'comments as comments_count',
                    'views as views_count',
                ])
                ->whereNull('deleted_at');

            if ($orderColumn === 'engagement_score') {
                return $query->get()
                    ->sortByDesc(fn (Article $a) => $a->viewCount() > 0
                        ? (($a->likes_count + $a->comments_count) / $a->viewCount()) * 100
                        : 0)
                    ->take($limit)
                    ->values()
                    ->map(fn (Article $a) => $this->formatTopArticleRow($a))
                    ->values();
            }

            return $query->orderByDesc($orderColumn)
                ->limit($limit)
                ->get()
                ->map(fn (Article $a) => $this->formatTopArticleRow($a))
                ->values();
        };

        $hourlyViews = View::query()
            ->where(fn ($q) => $articleMorph($q, 'viewable_type'))
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(DB::raw(SqlDialect::hour('created_at').' as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour');

        $hourlyChart = collect(range(0, 23))->map(fn ($h) => [
            'hour' => $h,
            'label' => sprintf('%02d:00', $h),
            'count' => (int) ($hourlyViews[$h] ?? 0),
        ])->values();

        return response()->json([
            'message' => 'Success',
            'analytics' => [
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo,
                    'days' => $periodDays,
                ],
                'summary' => [
                    'views' => $periodViews,
                    'likes' => $periodLikes,
                    'comments' => $periodComments,
                    'bookmarks' => $periodBookmarks,
                    'new_articles' => (int) $dailyTrend->sum('new_articles'),
                ],
                'trends' => [
                    'views' => $this->compareMetric($periodViews, $previousViews),
                    'likes' => $this->compareMetric($periodLikes, $previousLikes),
                ],
                'daily_trend' => $dailyTrend,
                'status_distribution' => $statusDistribution,
                'category_distribution' => $categoryDistribution,
                'engagement_mix' => $engagementMix,
                'hourly_views' => $hourlyChart,
                'top_by_views' => $formatTopArticles('views_count'),
                'top_by_likes' => $formatTopArticles('likes_count'),
                'top_by_bookmarks' => $formatTopArticles('bookmarks_count'),
                'top_by_comments' => $formatTopArticles('comments_count'),
                'top_by_engagement' => $formatTopArticles('engagement_score'),
            ],
        ]);
    }

    private function formatTopArticleRow(Article $a): array
    {
        $views = $a->viewCount();

        return [
            'id' => $a->id,
            'title' => $a->title,
            'slug' => $a->slug,
            'category' => $a->category?->title,
            'user' => $a->user ? $a->user->first_name.' '.$a->user->last_name : null,
            'views_count' => $views,
            'likes_count' => (int) $a->likes_count,
            'bookmarks_count' => (int) $a->bookmarks_count,
            'comments_count' => (int) $a->comments_count,
            'engagement_score' => $views > 0
                ? round((($a->likes_count + $a->comments_count) / $views) * 100, 2)
                : 0,
        ];
    }

    public function articleAnalytics(Article $article, Request $request)
    {
        [$dateFrom, $dateTo] = array_slice($this->resolveAnalyticsDateRange($request), 0, 2);
        $period = CarbonPeriod::create($dateFrom, $dateTo);

        $article->loadCount([
            'likes as likes_count' => fn ($q) => $q->where('type', 'like'),
            'bookmarkers as bookmarks_count',
            'comments as comments_count',
            'views as views_count',
        ]);

        $viewsDaily = $article->views()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(DB::raw(SqlDialect::date('created_at').' as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $likesDaily = $article->likes()
            ->where('type', 'like')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(DB::raw(SqlDialect::date('created_at').' as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $commentsDaily = $article->comments()
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(DB::raw(SqlDialect::date('created_at').' as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $bookmarksDaily = Bookmark::query()
            ->where('bookmarkable_type', Article::class)
            ->where('bookmarkable_id', $article->id)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo)
            ->select(DB::raw(SqlDialect::date('created_at').' as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->pluck('count', 'date');

        $dailyTrend = collect($period)->map(function ($date) use ($viewsDaily, $likesDaily, $commentsDaily, $bookmarksDaily) {
            $key = $date->toDateString();

            return [
                'date' => $key,
                'views' => (int) ($viewsDaily[$key] ?? 0),
                'likes' => (int) ($likesDaily[$key] ?? 0),
                'comments' => (int) ($commentsDaily[$key] ?? 0),
                'bookmarks' => (int) ($bookmarksDaily[$key] ?? 0),
            ];
        })->values();

        $views = $article->viewCount();
        $likes = (int) $article->likes_count;
        $comments = (int) $article->comments_count;
        $bookmarks = (int) $article->bookmarks_count;

        return response()->json([
            'message' => 'Success',
            'analytics' => [
                'totals' => [
                    'views' => $views,
                    'likes' => $likes,
                    'comments' => $comments,
                    'bookmarks' => $bookmarks,
                    'likes_per_100_views' => $views > 0 ? round(($likes / $views) * 100, 2) : 0,
                    'comments_per_100_views' => $views > 0 ? round(($comments / $views) * 100, 2) : 0,
                    'bookmarks_per_100_views' => $views > 0 ? round(($bookmarks / $views) * 100, 2) : 0,
                ],
                'engagement_mix' => [
                    'labels' => ['بازدید', 'لایک', 'نظر', 'بوکمارک'],
                    'data' => [$views, $likes, $comments, $bookmarks],
                    'colors' => ['#60a5fa', '#f472b6', '#22d3ee', '#a78bfa'],
                ],
                'daily_trend' => $dailyTrend,
                'period' => ['from' => $dateFrom, 'to' => $dateTo],
            ],
        ]);
    }

    private function resolveAnalyticsDateRange(Request $request): array
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $periodDays = max(1, (int) Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1);
        $previousFrom = Carbon::parse($dateFrom)->subDays($periodDays)->toDateString();
        $previousTo = Carbon::parse($dateFrom)->subDay()->toDateString();

        return [$dateFrom, $dateTo, $periodDays, $previousFrom, $previousTo];
    }

    private function compareMetric(int $current, int $previous): array
    {
        $changePercent = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : ($current > 0 ? 100 : 0);

        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $current - $previous,
            'change_percent' => $changePercent,
        ];
    }

    public function uploadCover(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'article_id' => ['required', 'exists:articles,id'],
            'filename' => ['required', 'string'],
            'mime' => ['required', 'string', 'in:image/jpeg,image/png,image/webp,image/gif'],
            'size' => ['required', 'integer', 'min:1', 'max:5242880'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $article = Article::findOrFail($request->article_id);
        $validData = $validator->validated();
        $disk = 'static';
        $folder = 'cover/article/' . date('Y/m/d');
        $ext = pathinfo($validData['filename'], PATHINFO_EXTENSION);
        $filePath = "{$folder}/" . Str::uuid()->toString() . ".{$ext}";

        $tokenData = UploadTokenService::generate([
            'sub' => 'upload',
            'type' => 'cover',
            'disk' => $disk,
            'path' => $filePath,
            'mime' => $validData['mime'],
            'size' => (int) $validData['size'],
            'articleId' => $article->id,
            'userId' => optional(auth('api')->user())->id,
        ]);

        return response()->json([
            'message' => 'Upload initialized.',
            'uploadPath' => $filePath,
            'uploadToken' => $tokenData['token'],
            'workerUploadUrl' => rtrim(config('upload.worker_base_url'), '/') . '/api/upload/attachment',
            'expiresAt' => $tokenData['expires_at'],
        ], 200);
    }

    public function removeCover(Article $article)
    {
        if ($article->cover_image) {
            $details = $this->urlDetails($article->cover_image);
            if ($details && $details['disk'] && $details['path'] && Storage::disk($details['disk'])->exists($details['path'])) {
                Storage::disk($details['disk'])->delete($details['path']);
            }
            $article->cover_image = null;
            $article->save();
        }

        return response()->json(['message' => 'Cover removed successfully'], 200);
    }

    public function storeCover(Request $request, Article $article)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'mimes:jpeg,png,webp,gif', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        if ($article->cover_image) {
            $details = $this->urlDetails($article->cover_image);
            if ($details && $details['disk'] && $details['path'] && Storage::disk($details['disk'])->exists($details['path'])) {
                Storage::disk($details['disk'])->delete($details['path']);
            }
        }

        $disk = 'static';
        $folder = 'cover/article/'.date('Y/m/d');
        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $filePath = "{$folder}/".Str::uuid()->toString().".{$ext}";

        Storage::disk($disk)->putFileAs($folder, $file, basename($filePath));

        $diskUrl = rtrim(config("filesystems.disks.{$disk}.url"), '/');
        $url = "{$diskUrl}/{$filePath}";

        $article->cover_image = $url;
        $article->save();

        return response()->json([
            'message' => 'Cover uploaded successfully',
            'cover_image' => $url,
        ], 200);
    }

    private function urlDetails(?string $url): ?array
    {
        if (! $url || ! Str::is('http*://*', $url)) {
            return null;
        }

        foreach (config('filesystems.disks') as $disk => $config) {
            if (! isset($config['url'])) {
                continue;
            }
            $baseUrl = rtrim($config['url'], '/');
            if (str_starts_with($url, $baseUrl)) {
                return [
                    'disk' => $disk,
                    'path' => ltrim(str_replace($baseUrl, '', $url), '/'),
                    'url' => $url,
                ];
            }
        }

        return null;
    }
}
