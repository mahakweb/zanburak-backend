<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use App\Models\View;
use App\Services\UploadTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
        ])->withCount(['likes as likes_count', 'bookmarkers as bookmarks_count', 'views as views_count']);

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

        $articles = $query->orderByDesc('id')->paginate($perPage);

        $articles->getCollection()->transform(function (Article $article) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'cover_image' => $article->cover_image,
                'reading_time_minutes' => $article->reading_time_minutes,
                'views_count' => $article->viewCount(),
                'likes_count' => $article->likes_count,
                'bookmarks_count' => $article->bookmarks_count,
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
            'tags' => 'nullable|array|max:5',
            'tags.*' => 'string|max:30',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $fields = [
            'user_id', 'category_id', 'title', 'english_title', 'excerpt', 'content', 'cover_image',
            'meta_keywords', 'seo_title', 'seo_description', 'canonical_url', 'og_image',
            'publish', 'status', 'is_featured', 'scheduled_at',
        ];

        $updateData = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $updateData[$field] = $request->input($field);
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
            'action' => 'required|in:publish,unpublish,delete,restore,archive,feature,unfeature',
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
            default => null,
        };

        return response()->json(['message' => 'Bulk action completed', 'affected' => $count]);
    }

    public function stats()
    {
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
                'total_views' => (int) View::where('viewable_type', Article::class)->count(),
                'avg_reading_time' => round((float) Article::avg('reading_time_minutes'), 1),
            ],
        ]);
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
