<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Course;
use App\Models\Tag;
use App\Models\User;
use App\Models\View;
use App\Rules\ValidTagName;
use LaravelInteraction\Bookmark\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth('api')->user();
        $categories = $request->input('cat', []);
        $categoryId = $request->input('category_id');
        $filter = $request->input('filter', '');
        $search = $request->input('search', '');
        $sort = $request->input('sort', 'latest');
        $authorId = $request->input('author_id');
        $tag = $request->input('tag');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $readingTimeMin = $request->input('reading_time_min');
        $readingTimeMax = $request->input('reading_time_max');

        $categoryIds = [];
        $categoryParam = $request->input('category');
        if ($categoryId) {
            $categoryIds = [(int) $categoryId];
        } elseif ($categoryParam) {
            $categoryIds = ArticleCategory::query()
                ->where(function ($q) use ($categoryParam) {
                    $q->where('slug', $categoryParam)
                        ->orWhere('english_title', $categoryParam);
                    if (is_numeric($categoryParam)) {
                        $q->orWhere('id', (int) $categoryParam);
                    }
                })
                ->pluck('id')
                ->toArray();
        } elseif (! empty($categories)) {
            $categoryIds = ArticleCategory::whereIn('title', (array) $categories)
                ->orWhereIn('slug', (array) $categories)
                ->pluck('id')
                ->toArray();
        }

        $query = Article::published()
            ->filter($filter)
            ->categoryId($categoryIds)
            ->authorId($authorId)
            ->tag($tag)
            ->search($search)
            ->sort($sort);

        if ($dateFrom) {
            $query->whereDate('published_at', '>=', $dateFrom);
        }
        if ($request->filled('published_after')) {
            $query->whereDate('published_at', '>=', $request->input('published_after'));
        }
        if ($dateTo) {
            $query->whereDate('published_at', '<=', $dateTo);
        }
        if ($readingTimeMin) {
            $query->where('reading_time_minutes', '>=', (int) $readingTimeMin);
        }
        if ($readingTimeMax) {
            $query->where('reading_time_minutes', '<=', (int) $readingTimeMax);
        }

        $perPage = (int) $request->input('perPage', 10);
        $page = max(1, (int) $request->input('page', 1));

        $total = $query->count();
        $lastPage = max(1, (int) ceil($total / $perPage));

        $articles = $query->with([
            'user:id,first_name,last_name,username,profile_pic',
            'category:id,title,english_title,slug',
        ])
            ->withCount(['likes as likes_count', 'bookmarkers as bookmarks_count', 'comments as comments_count', 'views as views_count'])
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $items = $articles->map(fn (Article $article) => $this->formatArticleListItem($article, $user));

        return response()->json([
            'message' => 'Success',
            'articles' => $items,
            'pagination' => [
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ],
        ]);
    }

    public function featured(Request $request)
    {
        $user = auth('api')->user();
        $limit = min(12, max(1, (int) $request->input('limit', 6)));

        $articles = Article::published()
            ->where('is_featured', true)
            ->with(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,slug'])
            ->withCount(['likes as likes_count', 'bookmarkers as bookmarks_count', 'comments as comments_count', 'views as views_count'])
            ->orderByDesc('published_at')
            ->take($limit)
            ->get();

        return response()->json([
            'message' => 'Success',
            'articles' => $articles->map(fn ($a) => $this->formatArticleListItem($a, $user)),
        ]);
    }

    public function sections(Request $request)
    {
        $user = auth('api')->user();
        $limit = min(10, max(1, (int) $request->input('limit', 5)));

        $baseQuery = fn () => Article::published()
            ->with(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,slug'])
            ->withCount(['likes as likes_count', 'bookmarkers as bookmarks_count', 'comments as comments_count', 'views as views_count']);

        $featured = (clone $baseQuery())->where('is_featured', true)->orderByDesc('published_at')->take($limit)->get();
        $newest = (clone $baseQuery())->orderByDesc('published_at')->take($limit)->get();
        $popular = (clone $baseQuery())->orderByDesc(Article::viewsCountSubquery())->take($limit)->get();
        $trending = (clone $baseQuery())
            ->where('published_at', '>=', now()->subWeek())
            ->orderByDesc(Article::viewsCountSubquery())
            ->take($limit)
            ->get();

        $categories = ArticleCategory::active()->ordered()->withCount([
            'articles as articles_count' => fn ($q) => $q->published(),
        ])->get(['id', 'title', 'english_title', 'slug', 'icon']);

        $popularTags = Tag::query()
            ->whereHas('articles', fn ($q) => $q->where('publish', true)->where('status', 'published'))
            ->withCount(['articles as articles_count' => fn ($q) => $q->where('publish', true)->where('status', 'published')])
            ->orderByDesc('articles_count')
            ->take(24)
            ->get()
            ->map(fn ($tag) => [
                'id' => $tag->tag_id,
                'name' => $tag->name,
                'slug' => $tag->normalized,
                'articles_count' => (int) $tag->articles_count,
            ]);

        $bookmarkedArticles = collect();
        if ($user) {
            $bookmarkIds = Bookmark::query()
                ->where('user_id', $user->id)
                ->where('bookmarkable_type', Article::class)
                ->latest()
                ->take(5)
                ->pluck('bookmarkable_id');

            $bookmarkedArticles = Article::published()
                ->whereIn('id', $bookmarkIds)
                ->with(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,slug'])
                ->withCount(['likes as likes_count', 'comments as comments_count'])
                ->get()
                ->map(fn ($a) => $this->formatArticleListItem($a, $user))
                ->values();
        }

        return response()->json([
            'message' => 'Success',
            'featured' => $featured->map(fn ($a) => $this->formatArticleListItem($a, $user)),
            'newest' => $newest->map(fn ($a) => $this->formatArticleListItem($a, $user)),
            'popular' => $popular->map(fn ($a) => $this->formatArticleListItem($a, $user)),
            'trending' => $trending->map(fn ($a) => $this->formatArticleListItem($a, $user)),
            'categories' => $categories,
            'popular_tags' => $popularTags,
            'bookmarked_articles' => $bookmarkedArticles,
        ]);
    }

    public function show(Request $request, Article $articleSlug)
    {
        $article = $articleSlug;

        if (! $article->publish || $article->status !== 'published' || $article->trashed()) {
            $user = auth('api')->user();
            if (! $user || ! $article->isEditableBy($user)) {
                return response()->json(['message' => 'Article not found.'], 404);
            }
        }

        View::createFor($article);

        $user = auth('api')->user();
        $article->load([
            'user:id,first_name,last_name,username,profile_pic',
            'user.info:id,user_id,job,about',
            'category:id,title,english_title,slug',
        ]);
        $article->loadCount(['likes as likes_count', 'bookmarkers as bookmarks_count', 'comments', 'views as views_count']);

        $authorArticlesCount = Article::published()->where('user_id', $article->user_id)->count();
        $authorRecentArticles = Article::published()
            ->where('user_id', $article->user_id)
            ->where('id', '!=', $article->id)
            ->with(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,slug'])
            ->withCount(['likes as likes_count', 'comments as comments_count'])
            ->orderByDesc('published_at')
            ->take(5)
            ->get()
            ->map(fn ($a) => $this->formatArticleListItem($a, $user));
        $followersCount = $article->user ? $article->user->followers()->count() : 0;
        $isFollowingAuthor = $user && $article->user ? $user->isFollowing($article->user) : false;

        return response()->json([
            'message' => 'Success',
            'article' => array_merge($this->formatArticleDetail($article, $user), [
                'ratings' => $this->formatArticleRatings($article, $user),
                'author' => [
                    'user' => $this->formatAuthorUser($article->user),
                    'articles_count' => $authorArticlesCount,
                    'recent_articles' => $authorRecentArticles,
                    'followers_count' => $followersCount,
                    'is_following' => $isFollowingAuthor,
                ],
            ]),
        ]);
    }

    public function related(Request $request, Article $articleSlug)
    {
        $article = $articleSlug;
        $limit = min(12, max(1, (int) $request->input('limit', 6)));
        $user = auth('api')->user();

        $article->loadMissing('tags');
        $tagIds = $article->tags->pluck('tag_id')->all();

        $related = Article::published()
            ->where('id', '!=', $article->id)
            ->when(! empty($tagIds), fn ($q) => $q->whereHasTagIds($tagIds), function ($q) use ($article) {
                $q->where('category_id', $article->category_id);
            })
            ->with(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,english_title,slug'])
            ->withCount(['likes as likes_count'])
            ->orderByDesc('published_at')
            ->take($limit)
            ->get();

        $relatedCourses = collect();
        if (! empty($tagIds)) {
            $relatedCourses = Course::query()
                ->where('publish', 1)
                ->whereHasTagIds($tagIds)
                ->with(['teacher:id,first_name,last_name,username,profile_pic'])
                ->orderByDesc('created_at')
                ->take($limit)
                ->get(['id', 'title', 'english_title', 'slug', 'poster', 'description', 'teacher_id']);
        }

        return response()->json([
            'message' => 'Success',
            'articles' => $related->map(fn ($a) => $this->formatArticleListItem($a, $user)),
            'courses' => $relatedCourses->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'poster' => $course->poster,
                'description' => $course->description,
                'teacher' => $course->teacher,
            ]),
        ]);
    }

    public function prevNext(Article $articleSlug)
    {
        $article = $articleSlug;

        $previous = Article::published()
            ->where('id', '<', $article->id)
            ->orderByDesc('id')
            ->first(['id', 'title', 'slug', 'cover_image']);

        $next = Article::published()
            ->where('id', '>', $article->id)
            ->orderBy('id')
            ->first(['id', 'title', 'slug', 'cover_image']);

        return response()->json([
            'message' => 'Success',
            'previous' => $previous,
            'next' => $next,
        ]);
    }

    public function getInitData(Request $request)
    {
        $categories = ArticleCategory::active()->ordered()->get(['id', 'title', 'english_title', 'slug', 'icon']);

        return response()->json([
            'message' => 'Success',
            'categories' => $categories,
        ]);
    }

    public function create(Request $request)
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
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
            'is_featured' => 'nullable|boolean',
            'scheduled_at' => 'nullable|date',
            'tags' => 'nullable|array|max:5',
            'tags.*' => ['string', 'max:30', new ValidTagName],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $publish = (bool) ($request->publish ?? false);
        $status = $publish ? 'published' : 'draft';

        $article = Article::create([
            'user_id' => $user->id,
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
            'published_at' => $publish ? now() : null,
        ]);

        if ($request->tags) {
            $article->tag($request->tags);
        }

        $article->load(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,slug']);

        return response()->json([
            'message' => 'Article created successfully',
            'article' => $this->formatArticleDetail($article, $user),
        ], 201);
    }

    public function getForEdit(Article $articleSlug)
    {
        $article = $articleSlug;
        $user = auth('api')->user();

        if (! $user || ! $article->isEditableBy($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $article->load(['category:id,title,slug']);

        return response()->json([
            'message' => 'Success',
            'article' => array_merge($article->only([
                'id', 'title', 'slug', 'excerpt', 'content', 'cover_image',
                'meta_keywords', 'seo_title', 'seo_description', 'canonical_url', 'og_image',
                'publish', 'status', 'is_featured', 'scheduled_at', 'category_id',
            ]), [
                'tags' => $this->formatTags($article->tags),
            ]),
        ]);
    }

    public function update(Request $request, Article $articleSlug)
    {
        $article = $articleSlug;
        $user = auth('api')->user();

        if (! $user || ! $article->isEditableBy($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
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
            'is_featured' => 'nullable|boolean',
            'scheduled_at' => 'nullable|date',
            'tags' => 'nullable|array|max:5',
            'tags.*' => ['string', 'max:30', new ValidTagName],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $data = $request->only([
            'category_id', 'title', 'english_title', 'excerpt', 'content', 'cover_image',
            'meta_keywords', 'seo_title', 'seo_description', 'canonical_url', 'og_image',
            'is_featured', 'scheduled_at',
        ]);

        if ($request->has('publish')) {
            $data['publish'] = (bool) $request->publish;
            $data['status'] = $request->publish ? 'published' : 'draft';
            if ($request->publish && ! $article->published_at) {
                $data['published_at'] = now();
            }
        }

        $article->update($data);

        if ($request->has('tags')) {
            $article->untag();
            if ($request->tags) {
                $article->tag($request->tags);
            }
        }

        $article->load(['user:id,first_name,last_name,username,profile_pic', 'category:id,title,slug']);

        return response()->json([
            'message' => 'Article updated successfully',
            'article' => $this->formatArticleDetail($article, $user),
        ]);
    }

    public function delete(Article $articleSlug)
    {
        $article = $articleSlug;
        $user = auth('api')->user();

        if (! $user || ! $article->isEditableBy($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $article->delete();

        return response()->json(['message' => 'Article deleted successfully']);
    }

    public function addTags(Request $request, Article $articleSlug)
    {
        $article = $articleSlug;
        $user = auth('api')->user();

        if (! $user || ! $article->isEditableBy($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'tags' => 'required|array|max:5',
            'tags.*' => ['string', 'max:30', new ValidTagName],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $article->retag($request->tags);

        return response()->json([
            'message' => 'Success',
            'tags' => $this->formatTags($article->tags()->get()),
        ]);
    }

    public function userArticles(Request $request, User $username)
    {
        $user = auth('api')->user();
        $perPage = (int) $request->input('perPage', 10);
        $page = max(1, (int) $request->input('page', 1));

        $query = Article::published()->where('user_id', $username->id);
        $total = $query->count();
        $lastPage = max(1, (int) ceil($total / $perPage));

        $articles = $query->with(['category:id,title,slug'])
            ->withCount(['likes as likes_count'])
            ->orderByDesc('published_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'message' => 'Success',
            'articles' => $articles->map(fn ($a) => $this->formatArticleListItem($a, $user)),
            'pagination' => [
                'total' => $total,
                'current_page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
            ],
        ]);
    }

    private function formatArticleRatings(Article $article, ?User $user): array
    {
        $currentUserRate = ($user && $article->checkUserRateThis($user->id))
            ? $article->checkUserRateThis($user->id)->only('rating', 'comment')
            : null;

        return [
            'countOfOne' => $article->sumOfRateNumber(1),
            'countOfTwo' => $article->sumOfRateNumber(2),
            'countOfThree' => $article->sumOfRateNumber(3),
            'countOfFour' => $article->sumOfRateNumber(4),
            'countOfFive' => $article->sumOfRateNumber(5),
            'countOfAll' => $article->sumOfAllRate(),
            'sumOfAll' => $article->sumRating(),
            'averageRating' => $article->averageRating(),
            'currentUserRate' => $currentUserRate,
        ];
    }

    private function formatArticleListItem(Article $article, ?User $user): array
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'english_title' => $article->english_title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'cover_image' => $article->cover_image,
            'reading_time_minutes' => $article->reading_time_minutes,
            'views_count' => $article->viewCount(),
            'likes_count' => (int) ($article->likes_count ?? $article->likes()->count()),
            'comments_count' => (int) ($article->comments_count ?? $article->comments()->where('approved', 1)->count()),
            'bookmarks_count' => (int) ($article->bookmarks_count ?? 0),
            'is_featured' => $article->is_featured,
            'published_at' => $article->published_at,
            'created_at' => $article->created_at,
            'updated_at' => $article->updated_at,
            'user' => $article->user,
            'category' => $article->category,
            'tags' => $this->formatTags($article->tags),
            'bookmarked' => $user ? $user->hasBookmarked($article) : false,
            'user_has_liked' => $user ? $user->hasLiked($article) : false,
            'is_editable' => $user ? $article->isEditableBy($user) : false,
        ];
    }

    private function formatArticleDetail(Article $article, ?User $user): array
    {
        return array_merge($this->formatArticleListItem($article, $user), [
            'content' => $article->content,
            'meta_keywords' => $article->meta_keywords,
            'seo_title' => $article->seo_title,
            'seo_description' => $article->seo_description,
            'canonical_url' => $article->canonical_url,
            'og_image' => $article->og_image,
            'comments_count' => (int) ($article->comments_count ?? 0),
            'status' => $article->status,
        ]);
    }

    private function formatTags($tags)
    {
        return collect($tags)->map(fn ($tag) => [
            'id' => $tag->tag_id,
            'name' => $tag->name,
            'slug' => $tag->normalized,
        ])->values();
    }

    private function formatAuthorUser(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        $data = $user->only(['id', 'first_name', 'last_name', 'username', 'profile_pic']);
        $data['info'] = $user->info?->job ?: $user->info?->about;

        return $data;
    }
}
