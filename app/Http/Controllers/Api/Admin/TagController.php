<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Course;
use App\Models\Question;
use App\Models\User;
use App\Models\Tag;
use App\Rules\ValidTagName;
use Cviebrock\EloquentTaggable\Services\TagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    public function stats()
    {
        $totalTags = Tag::count();
        $tagsWithQuestions = Tag::whereHas('questions')->count();
        $tagsWithCourses = Tag::whereHas('courses')->count();
        $tagsWithArticles = Tag::whereHas('articles')->count();
        $emptyTags = Tag::whereDoesntHave('questions')->whereDoesntHave('courses')->whereDoesntHave('articles')->count();
        $totalFollowers = DB::table('followables')
            ->where('followable_type', Tag::class)
            ->count();
        $createdThisMonth = Tag::where('created_at', '>=', now()->startOfMonth())->count();
        $createdToday = Tag::whereDate('created_at', today())->count();

        $topByQuestions = Tag::query()
            ->withCount(['questions' => fn ($q) => $q->where('publish', 1)])
            ->orderByDesc('questions_count')
            ->take(8)
            ->get()
            ->map(fn (Tag $tag) => $this->compactTag($tag));

        $topByCourses = Tag::query()
            ->withCount('courses')
            ->orderByDesc('courses_count')
            ->take(8)
            ->get()
            ->map(fn (Tag $tag) => $this->compactTag($tag));

        $topByFollowers = Tag::query()
            ->withCount('followers')
            ->orderByDesc('followers_count')
            ->take(8)
            ->get()
            ->map(fn (Tag $tag) => $this->compactTag($tag));

        $topByArticles = Tag::query()
            ->withCount(['articles as articles_count' => fn ($q) => $q->where('publish', 1)])
            ->orderByDesc('articles_count')
            ->take(8)
            ->get()
            ->map(fn (Tag $tag) => $this->compactTag($tag));

        $recentTags = Tag::query()
            ->orderByDesc('created_at')
            ->take(6)
            ->get()
            ->map(fn (Tag $tag) => $this->compactTag($tag));

        return response()->json([
            'message' => 'Success',
            'stats' => array_merge([
                'total_tags' => $totalTags,
                'tags_with_questions' => $tagsWithQuestions,
                'tags_with_courses' => $tagsWithCourses,
                'tags_with_articles' => $tagsWithArticles,
                'empty_tags' => $emptyTags,
                'total_followers' => $totalFollowers,
                'created_this_month' => $createdThisMonth,
                'created_today' => $createdToday,
                'top_by_questions' => $topByQuestions,
                'top_by_courses' => $topByCourses,
                'top_by_articles' => $topByArticles,
                'top_by_followers' => $topByFollowers,
                'recent_tags' => $recentTags,
            ], $this->globalAnalytics()),
        ]);
    }

    private function globalAnalytics(): array
    {
        $pivotTable = config('taggable.tables.taggable_taggables', 'taggable_taggables');
        $since = now()->subDays(29)->startOfDay();

        $onlyQuestions = Tag::whereHas('questions')->whereDoesntHave('courses')->whereDoesntHave('articles')->count();
        $onlyCourses = Tag::whereHas('courses')->whereDoesntHave('questions')->whereDoesntHave('articles')->count();
        $onlyArticles = Tag::whereHas('articles')->whereDoesntHave('questions')->whereDoesntHave('courses')->count();
        $mixed = Tag::query()
            ->where(function ($q) {
                $q->where(fn ($q2) => $q2->whereHas('questions')->whereHas('courses'))
                    ->orWhere(fn ($q2) => $q2->whereHas('questions')->whereHas('articles'))
                    ->orWhere(fn ($q2) => $q2->whereHas('courses')->whereHas('articles'))
                    ->orWhere(fn ($q2) => $q2->whereHas('questions')->whereHas('courses')->whereHas('articles'));
            })
            ->count();
        $empty = Tag::whereDoesntHave('questions')->whereDoesntHave('courses')->whereDoesntHave('articles')->count();
        $withFollowers = Tag::whereHas('followers')->count();

        $totalQuestionLinks = DB::table($pivotTable)
            ->where('taggable_type', Question::class)
            ->count();
        $totalCourseLinks = DB::table($pivotTable)
            ->where('taggable_type', Course::class)
            ->count();
        $totalArticleLinks = DB::table($pivotTable)
            ->where('taggable_type', Article::class)
            ->count();
        $totalFollowers = DB::table('followables')
            ->where('followable_type', Tag::class)
            ->count();

        $tagsCreated = DB::table(config('taggable.tables.taggable_tags', 'taggable_tags'))
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $questionLinks = DB::table($pivotTable)
            ->where('taggable_type', Question::class)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $courseLinks = DB::table($pivotTable)
            ->where('taggable_type', Course::class)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $articleLinks = DB::table($pivotTable)
            ->where('taggable_type', Article::class)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $followerLinks = DB::table('followables')
            ->where('followable_type', Tag::class)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $topForBar = Tag::query()
            ->withCount(['questions' => fn ($q) => $q->where('publish', 1)])
            ->orderByDesc('questions_count')
            ->take(8)
            ->get();

        return [
            'analytics' => [
                'tag_usage_distribution' => [
                    'labels' => ['فقط سوال', 'فقط دوره', 'فقط مقاله', 'ترکیبی', 'بدون محتوا'],
                    'data' => [$onlyQuestions, $onlyCourses, $onlyArticles, $mixed, $empty],
                    'colors' => ['#fbbf24', '#60a5fa', '#34d399', '#a78bfa', '#9ca3af'],
                ],
                'tag_status_distribution' => [
                    'labels' => ['با سوال', 'با دوره', 'با مقاله', 'بدون محتوا', 'با دنبال‌کننده'],
                    'data' => [
                        Tag::whereHas('questions')->count(),
                        Tag::whereHas('courses')->count(),
                        Tag::whereHas('articles')->count(),
                        $empty,
                        $withFollowers,
                    ],
                    'colors' => ['#fbbf24', '#60a5fa', '#34d399', '#9ca3af', '#a78bfa'],
                ],
                'ecosystem_overview' => [
                    'labels' => ['تگ‌ها', 'اتصال سوال', 'اتصال دوره', 'اتصال مقاله', 'دنبال‌کننده'],
                    'data' => [Tag::count(), $totalQuestionLinks, $totalCourseLinks, $totalArticleLinks, $totalFollowers],
                    'colors' => ['#f59e0b', '#fbbf24', '#60a5fa', '#34d399', '#a78bfa'],
                ],
                'tags_created_timeline' => $this->buildDateSeries($tagsCreated),
                'question_links_timeline' => $this->buildDateSeries($questionLinks),
                'course_links_timeline' => $this->buildDateSeries($courseLinks),
                'article_links_timeline' => $this->buildDateSeries($articleLinks),
                'followers_timeline' => $this->buildDateSeries($followerLinks),
                'top_tags_questions_bar' => [
                    'labels' => $topForBar->pluck('name')->all(),
                    'data' => $topForBar->pluck('questions_count')->all(),
                ],
            ],
        ];
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->input('perPage', 15);
        $search = trim((string) $request->input('search', ''));
        $sort = $request->input('sort', 'popular');
        $filter = $request->input('filter', 'all');

        $query = Tag::query()
            ->withCount([
                'followers',
                'questions as questions_count' => fn ($q) => $q->where('publish', 1),
                'courses as courses_count',
                'articles as articles_count' => fn ($q) => $q->where('publish', 1),
            ]);

        if ($search !== '') {
            $normalized = app(TagService::class)->normalize($search);
            $query->where(function ($q) use ($search, $normalized) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('normalized', 'like', "%{$normalized}%");
            });
        }

        match ($filter) {
            'with_questions' => $query->whereHas('questions', fn ($q) => $q->where('publish', 1)),
            'with_courses' => $query->whereHas('courses'),
            'with_articles' => $query->whereHas('articles', fn ($q) => $q->where('publish', 1)),
            'empty' => $query->whereDoesntHave('questions')->whereDoesntHave('courses')->whereDoesntHave('articles'),
            'with_followers' => $query->whereHas('followers'),
            default => null,
        };

        match ($sort) {
            'name' => $query->orderBy('name'),
            'followers' => $query->orderByDesc('followers_count'),
            'courses' => $query->orderByDesc('courses_count'),
            'articles' => $query->orderByDesc('articles_count'),
            'created_at' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('questions_count'),
        };

        $tags = $query->paginate($perPage);

        $tags->getCollection()->transform(function (Tag $tag) {
            return $this->listTag($tag);
        });

        return response()->json([
            'message' => 'Success',
            'tags' => $tags,
        ]);
    }

    public function show(Tag $tag)
    {
        $tag->loadCount([
            'followers',
            'questions as questions_count' => fn ($q) => $q->where('publish', 1),
            'questions as questions_total_count',
            'courses as courses_count',
            'articles as articles_count' => fn ($q) => $q->where('publish', 1),
            'articles as articles_total_count',
        ]);

        return response()->json([
            'message' => 'Success',
            'tag' => $this->detailTag($tag),
        ]);
    }

    public function questions(Request $request, Tag $tag)
    {
        $perPage = (int) $request->input('perPage', 10);

        $questions = $tag->questions()
            ->with(['user:id,first_name,last_name,username'])
            ->orderByDesc('questions.created_at')
            ->paginate($perPage);

        $questions->getCollection()->transform(function (Question $question) {
            return [
                'id' => $question->id,
                'subject' => $question->subject,
                'publish' => (bool) $question->publish,
                'user' => $question->user ? [
                    'id' => $question->user->id,
                    'first_name' => $question->user->first_name,
                    'last_name' => $question->user->last_name,
                    'username' => $question->user->username,
                ] : null,
                'created_at' => $question->created_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'questions' => $questions,
        ]);
    }

    public function courses(Request $request, Tag $tag)
    {
        $perPage = (int) $request->input('perPage', 10);

        $courses = $tag->courses()
            ->select([
                'courses.id',
                'courses.title',
                'courses.english_title',
                'courses.slug',
                'courses.publish',
                'courses.status_id',
                'courses.created_at',
            ])
            ->orderByDesc('courses.created_at')
            ->paginate($perPage);

        $courses->getCollection()->transform(function (Course $course) {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'publish' => (bool) $course->publish,
                'status_id' => $course->status_id,
                'created_at' => $course->created_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'courses' => $courses,
        ]);
    }

    public function articles(Request $request, Tag $tag)
    {
        $perPage = (int) $request->input('perPage', 10);

        $articles = $tag->articles()
            ->with([
                'user:id,first_name,last_name,username',
                'category:id,title',
            ])
            ->select([
                'articles.id',
                'articles.title',
                'articles.english_title',
                'articles.slug',
                'articles.publish',
                'articles.status',
                'articles.is_featured',
                'articles.cover_image',
                'articles.user_id',
                'articles.category_id',
                'articles.published_at',
                'articles.created_at',
            ])
            ->orderByDesc('articles.created_at')
            ->paginate($perPage);

        $articles->getCollection()->transform(function (Article $article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'english_title' => $article->english_title,
                'slug' => $article->slug,
                'publish' => (bool) $article->publish,
                'status' => $article->status,
                'is_featured' => (bool) $article->is_featured,
                'cover_image' => $article->cover_image,
                'published_at' => $article->published_at,
                'created_at' => $article->created_at,
                'user' => $article->user ? [
                    'id' => $article->user->id,
                    'first_name' => $article->user->first_name,
                    'last_name' => $article->user->last_name,
                    'username' => $article->user->username,
                ] : null,
                'category' => $article->category ? [
                    'id' => $article->category->id,
                    'title' => $article->category->title,
                ] : null,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'articles' => $articles,
        ]);
    }

    public function followers(Request $request, Tag $tag)
    {
        $perPage = (int) $request->input('perPage', 15);

        $followers = $tag->followers()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.username', 'users.profile_pic')
            ->orderByPivot('created_at', 'desc')
            ->paginate($perPage);

        $followers->getCollection()->transform(function (User $user) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'profile_pic' => $user->profile_pic,
                'followed_at' => $user->pivot?->created_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'followers' => $followers,
        ]);
    }

    public function analytics(Tag $tag)
    {
        $publishedQuestions = $tag->questions()->where('publish', 1)->count();
        $draftQuestions = $tag->questions()->where('publish', 0)->count();
        $coursesCount = $tag->courses()->count();
        $publishedArticles = $tag->articles()->where('publish', 1)->count();
        $draftArticles = $tag->articles()->where('publish', 0)->count();
        $followersCount = $tag->followers()->count();

        $pivotTable = config('taggable.tables.taggable_taggables', 'taggable_taggables');
        $since = now()->subDays(29)->startOfDay();

        $questionLinks = DB::table($pivotTable)
            ->where('tag_id', $tag->tag_id)
            ->where('taggable_type', Question::class)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $courseLinks = DB::table($pivotTable)
            ->where('tag_id', $tag->tag_id)
            ->where('taggable_type', Course::class)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $articleLinks = DB::table($pivotTable)
            ->where('tag_id', $tag->tag_id)
            ->where('taggable_type', Article::class)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        $followerLinks = DB::table('followables')
            ->where('followable_type', Tag::class)
            ->where('followable_id', $tag->tag_id)
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');

        return response()->json([
            'message' => 'Success',
            'analytics' => [
                'content_distribution' => [
                    'labels' => ['سوال منتشر', 'سوال پیش‌نویس', 'دوره‌ها', 'مقاله منتشر', 'مقاله پیش‌نویس'],
                    'data' => [$publishedQuestions, $draftQuestions, $coursesCount, $publishedArticles, $draftArticles],
                    'colors' => ['#34d399', '#9ca3af', '#60a5fa', '#10b981', '#d1d5db'],
                ],
                'usage_overview' => [
                    'labels' => ['سوالات', 'دوره‌ها', 'مقالات', 'دنبال‌کننده'],
                    'data' => [$publishedQuestions + $draftQuestions, $coursesCount, $publishedArticles + $draftArticles, $followersCount],
                    'colors' => ['#fbbf24', '#60a5fa', '#34d399', '#a78bfa'],
                ],
                'questions_timeline' => $this->buildDateSeries($questionLinks),
                'courses_timeline' => $this->buildDateSeries($courseLinks),
                'articles_timeline' => $this->buildDateSeries($articleLinks),
                'followers_timeline' => $this->buildDateSeries($followerLinks),
            ],
        ]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:20', new ValidTagName()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newName = trim($request->name);
        $normalized = app(TagService::class)->normalize($newName);

        if (Tag::where('normalized', $normalized)->exists()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => ['name' => ['این تگ از قبل وجود دارد.']],
            ], 422);
        }

        $tag = Tag::create(['name' => $newName]);
        $tag->loadCount([
            'followers',
            'questions as questions_count' => fn ($q) => $q->where('publish', 1),
            'courses as courses_count',
            'articles as articles_count' => fn ($q) => $q->where('publish', 1),
        ]);

        return response()->json([
            'message' => 'Tag created successfully',
            'tag' => $this->listTag($tag),
        ], 201);
    }

    public function update(Request $request, Tag $tag)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:20', new ValidTagName()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newName = trim($request->name);
        $normalized = app(TagService::class)->normalize($newName);

        $exists = Tag::where('normalized', $normalized)
            ->where('tag_id', '!=', $tag->tag_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => ['name' => ['این تگ از قبل وجود دارد.']],
            ], 422);
        }

        $tag->name = $newName;
        $tag->save();

        $tag->loadCount([
            'followers',
            'questions as questions_count' => fn ($q) => $q->where('publish', 1),
            'courses as courses_count',
            'articles as articles_count' => fn ($q) => $q->where('publish', 1),
        ]);

        return response()->json([
            'message' => 'Tag updated successfully',
            'tag' => $this->listTag($tag),
        ]);
    }

    public function delete(Tag $tag)
    {
        $this->purgeTag($tag);

        return response()->json([
            'message' => 'Tag deleted successfully',
        ]);
    }

    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:taggable_tags,tag_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deleted = 0;
        foreach ($request->ids as $id) {
            $tag = Tag::find($id);
            if ($tag) {
                $this->purgeTag($tag);
                $deleted++;
            }
        }

        return response()->json([
            'message' => 'Tags deleted successfully',
            'deleted' => $deleted,
        ]);
    }

    public function merge(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source_id' => 'required|integer|exists:taggable_tags,tag_id',
            'target_id' => 'required|integer|exists:taggable_tags,tag_id|different:source_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $source = Tag::findOrFail($request->source_id);
        $target = Tag::findOrFail($request->target_id);

        $pivotTable = config('taggable.tables.taggable_taggables', 'taggable_taggables');

        DB::transaction(function () use ($source, $target, $pivotTable) {
            $rows = DB::table($pivotTable)->where('tag_id', $source->tag_id)->get();

            foreach ($rows as $row) {
                $exists = DB::table($pivotTable)
                    ->where('tag_id', $target->tag_id)
                    ->where('taggable_id', $row->taggable_id)
                    ->where('taggable_type', $row->taggable_type)
                    ->exists();

                if (!$exists) {
                    DB::table($pivotTable)->insert([
                        'tag_id' => $target->tag_id,
                        'taggable_id' => $row->taggable_id,
                        'taggable_type' => $row->taggable_type,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table($pivotTable)->where('tag_id', $source->tag_id)->delete();
            DB::table('followables')
                ->where('followable_type', Tag::class)
                ->where('followable_id', $source->tag_id)
                ->delete();

            $source->delete();
        });

        $target->loadCount([
            'followers',
            'questions as questions_count' => fn ($q) => $q->where('publish', 1),
            'courses as courses_count',
            'articles as articles_count' => fn ($q) => $q->where('publish', 1),
        ]);

        return response()->json([
            'message' => 'Tags merged successfully',
            'tag' => $this->listTag($target),
        ]);
    }

    public function search(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $limit = min(20, max(1, (int) $request->input('limit', 10)));

        $query = Tag::query()
            ->withCount([
                'questions as questions_count' => fn ($q) => $q->where('publish', 1),
                'courses as courses_count',
                'articles as articles_count' => fn ($q) => $q->where('publish', 1),
            ]);

        if ($search !== '') {
            $normalized = app(TagService::class)->normalize($search);
            $query->where(function ($q) use ($search, $normalized) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('normalized', 'like', "%{$normalized}%");
            });
        }

        $tags = $query->orderBy('name')->take($limit)->get()
            ->map(fn (Tag $tag) => $this->compactTag($tag));

        return response()->json([
            'message' => 'Success',
            'tags' => $tags,
        ]);
    }

    private function purgeTag(Tag $tag): void
    {
        $pivotTable = config('taggable.tables.taggable_taggables', 'taggable_taggables');

        DB::transaction(function () use ($tag, $pivotTable) {
            DB::table($pivotTable)->where('tag_id', $tag->tag_id)->delete();
            DB::table('followables')
                ->where('followable_type', Tag::class)
                ->where('followable_id', $tag->tag_id)
                ->delete();
            $tag->delete();
        });
    }

    private function compactTag(Tag $tag): array
    {
        return [
            'id' => $tag->tag_id,
            'name' => $tag->name,
            'slug' => $tag->normalized,
            'questions_count' => $tag->questions_count ?? 0,
            'courses_count' => $tag->courses_count ?? 0,
            'articles_count' => $tag->articles_count ?? 0,
            'followers_count' => $tag->followers_count ?? 0,
            'created_at' => $tag->created_at,
        ];
    }

    private function listTag(Tag $tag): array
    {
        return array_merge($this->compactTag($tag), [
            'updated_at' => $tag->updated_at,
        ]);
    }

    private function detailTag(Tag $tag): array
    {
        return array_merge($this->listTag($tag), [
            'questions_total_count' => $tag->questions_total_count ?? $tag->questions_count ?? 0,
            'articles_total_count' => $tag->articles_total_count ?? $tag->articles_count ?? 0,
        ]);
    }

    private function buildDateSeries($countsByDate, int $days = 30): array
    {
        $labels = [];
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = $date;
            $data[] = (int) ($countsByDate[$date] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
