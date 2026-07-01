<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ArticleCategoryController extends Controller
{
    public function index()
    {
        $categories = ArticleCategory::ordered()
            ->withCount([
                'articles as articles_count',
                'articles as published_articles_count' => fn ($q) => $q->where('publish', true),
                'articles as draft_articles_count' => fn ($q) => $q->where('publish', false),
                'articles as featured_articles_count' => fn ($q) => $q->where('is_featured', true),
            ])
            ->get();

        $totalArticles = (int) $categories->sum('articles_count');

        return response()->json([
            'message' => 'Success',
            'categories' => $categories,
            'stats' => [
                'total_categories' => $categories->count(),
                'active_categories' => $categories->where('status', true)->count(),
                'inactive_categories' => $categories->where('status', false)->count(),
                'empty_categories' => $categories->where('articles_count', 0)->count(),
                'total_articles' => $totalArticles,
                'published_articles' => (int) $categories->sum('published_articles_count'),
                'draft_articles' => (int) $categories->sum('draft_articles_count'),
                'featured_articles' => (int) $categories->sum('featured_articles_count'),
                'avg_articles_per_category' => $categories->count() > 0
                    ? round($totalArticles / $categories->count(), 1)
                    : 0,
            ],
        ]);
    }

    public function articles(Request $request, ArticleCategory $category)
    {
        $perPage = (int) $request->input('perPage', 15);

        $query = $category->articles()
            ->with([
                'user:id,first_name,last_name,username,profile_pic',
            ]);

        if ($request->boolean('published_only')) {
            $query->where('publish', true);
        }

        $articles = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $articles->getCollection()->transform(function ($article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'cover_image' => $article->cover_image,
                'publish' => $article->publish,
                'status' => $article->status,
                'is_featured' => $article->is_featured,
                'created_at' => $article->created_at,
                'user' => $article->user,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'category' => $category->only(['id', 'title', 'slug', 'english_title']),
            'articles' => $articles,
        ]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'english_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'parent_id' => 'nullable|exists:article_categories,id',
            'order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $category = ArticleCategory::create($request->only([
            'title', 'english_title', 'description', 'icon', 'parent_id', 'order', 'status',
        ]));

        return response()->json(['message' => 'Category created', 'category' => $category], 201);
    }

    public function update(Request $request, ArticleCategory $category)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'english_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'parent_id' => 'nullable|exists:article_categories,id',
            'order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $category->update($request->only([
            'title', 'english_title', 'description', 'icon', 'parent_id', 'order', 'status',
        ]));

        return response()->json(['message' => 'Category updated', 'category' => $category]);
    }

    public function delete(Request $request, ArticleCategory $category)
    {
        $articlesCount = $category->articles()->count();

        if ($articlesCount > 0) {
            if ($request->filled('transfer_to')) {
                $validator = Validator::make($request->all(), [
                    'transfer_to' => 'required|integer|exists:article_categories,id|not_in:'.$category->id,
                ], [
                    'transfer_to.required' => 'انتخاب دسته مقصد برای انتقال مقالات الزامی است.',
                    'transfer_to.not_in' => 'دسته مقصد نمی‌تواند همان دسته‌ای باشد که حذف می‌شود.',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'message' => 'برای حذف این دسته، ابتدا مقالات را به دسته دیگری منتقل کنید.',
                        'errors' => $validator->errors(),
                    ], 422);
                }

                $target = ArticleCategory::findOrFail($request->integer('transfer_to'));

                DB::transaction(function () use ($category, $target) {
                    $category->articles()->update(['category_id' => $target->id]);
                    $category->delete();
                });

                return response()->json([
                    'message' => 'Category deleted',
                    'transferred_articles' => $articlesCount,
                    'transfer_to' => $target->only(['id', 'title', 'slug']),
                ]);
            }

            $validator = Validator::make($request->all(), [
                'transfers' => 'required|array|min:1',
                'transfers.*.article_id' => 'required|integer|distinct',
                'transfers.*.category_id' => 'required|integer|exists:article_categories,id|not_in:'.$category->id,
            ], [
                'transfers.required' => 'تعیین مقصد برای همه مقالات الزامی است.',
                'transfers.*.category_id.not_in' => 'مقصد انتقال نمی‌تواند همان دسته حذف‌شونده باشد.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'برای حذف این دسته، مقصد همه مقالات را مشخص کنید.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $categoryArticleIds = $category->articles()->pluck('id')->sort()->values();
            $transferArticleIds = collect($request->input('transfers'))->pluck('article_id')->sort()->values();

            if ($categoryArticleIds->count() !== $transferArticleIds->count()
                || $categoryArticleIds->diff($transferArticleIds)->isNotEmpty()) {
                return response()->json([
                    'message' => 'باید برای همه مقالات این دسته مقصد انتقال مشخص شود.',
                ], 422);
            }

            $invalidArticles = Article::query()
                ->whereIn('id', $transferArticleIds)
                ->where('category_id', '!=', $category->id)
                ->exists();

            if ($invalidArticles) {
                return response()->json([
                    'message' => 'برخی مقالات انتخاب‌شده متعلق به این دسته نیستند.',
                ], 422);
            }

            $transfers = collect($request->input('transfers'));
            $summary = $transfers
                ->groupBy('category_id')
                ->map(fn ($items, $categoryId) => [
                    'category_id' => (int) $categoryId,
                    'count' => $items->count(),
                ])
                ->values();

            DB::transaction(function () use ($category, $transfers) {
                foreach ($transfers as $transfer) {
                    $category->articles()
                        ->where('id', $transfer['article_id'])
                        ->update(['category_id' => $transfer['category_id']]);
                }
                $category->delete();
            });

            $targetCategories = ArticleCategory::query()
                ->whereIn('id', $summary->pluck('category_id'))
                ->get(['id', 'title', 'slug'])
                ->keyBy('id');

            return response()->json([
                'message' => 'Category deleted',
                'transferred_articles' => $articlesCount,
                'transfer_summary' => $summary->map(function ($row) use ($targetCategories) {
                    $target = $targetCategories->get($row['category_id']);

                    return [
                        'count' => $row['count'],
                        'category' => $target?->only(['id', 'title', 'slug']),
                    ];
                })->values(),
            ]);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted']);
    }

    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:article_categories,id',
            'action' => 'required|in:activate,deactivate',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $status = $request->input('action') === 'activate';
        $affected = ArticleCategory::whereIn('id', $request->input('ids'))
            ->where('status', '!=', $status)
            ->update(['status' => $status]);

        return response()->json([
            'message' => 'Bulk action completed',
            'affected' => $affected,
        ]);
    }
}
