<?php

namespace App\Models;

use App\Contracts\Likeable;
use App\Models\Concerns\Likes;
use App\Models\Rating;
use App\Services\Search\SearchTermExtractor;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentTaggable\Taggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use LaravelInteraction\Bookmark\Concerns\Bookmarkable;
use Mehradsadeghi\FilterQueryString\FilterQueryString;

class Article extends Model implements Likeable
{
    use HasFactory,
        Searchable,
        Sluggable,
        FilterQueryString,
        Taggable,
        Bookmarkable,
        Likes,
        SoftDeletes;

    protected $filters = ['category_id', 'filter', 'author_id', 'tag', 'sort'];

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'english_title',
        'excerpt',
        'content',
        'cover_image',
        'meta_keywords',
        'seo_title',
        'seo_description',
        'canonical_url',
        'og_image',
        'reading_time_minutes',
        'publish',
        'status',
        'is_featured',
        'scheduled_at',
        'published_at',
    ];

    protected $casts = [
        'publish' => 'boolean',
        'is_featured' => 'boolean',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'reading_time_minutes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Article $article) {
            $article->reading_time_minutes = self::calculateReadingTime($article->content ?? '');

            if ($article->publish && ! $article->published_at) {
                $article->published_at = now();
            }

            if ($article->publish && $article->status === 'draft') {
                $article->status = 'published';
            }
        });
    }

    public static function calculateReadingTime(string $content): int
    {
        $plain = trim(strip_tags(preg_replace('/```[\s\S]*?```/', '', $content)));
        $words = preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY);
        $count = is_array($words) ? count($words) : 0;

        return max(1, (int) ceil($count / 200));
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing(['tags', 'category', 'user']);
        $extractor = app(SearchTermExtractor::class);

        $searchTerms = $extractor->fromDocument(
            title: $this->title,
            englishTitle: $this->english_title,
            metaKeywords: $this->meta_keywords,
            tags: $this->tags->pluck('name')->all(),
            categories: array_filter([$this->category?->title, $this->category?->english_title]),
        );
        $searchTerms = $extractor->uniqueTerms([
            ...$searchTerms,
            ...$extractor->extractFromText($this->excerpt ?? ''),
            ...$extractor->extractFromText($this->content),
        ]);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'english_title' => $this->english_title,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'slug' => $this->slug,
            'meta_keywords' => $this->meta_keywords,
            'search_terms' => implode(' ', $searchTerms),
            'tags_text' => $this->tags->pluck('name')->join(' '),
            'category_title' => $this->category?->title,
            'author_name' => trim(($this->user?->first_name ?? '') . ' ' . ($this->user?->last_name ?? '')),
            'publish' => (bool) $this->publish,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->publish && $this->status === 'published' && ! $this->trashed();
    }

    public function getSlugSourceAttribute(): string
    {
        return trim((string) ($this->english_title ?: $this->title));
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'slugSource',
                'onUpdate' => true,
            ],
        ];
    }

    public function scopeSearch($query, $searchKey)
    {
        if ($searchKey) {
            return $query->whereIn('id', Article::search($searchKey)->keys());
        }

        return $query;
    }

    public function scopePublished($query)
    {
        return $query->where('publish', true)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            });
    }

    public function scopeFilter($query, $value)
    {
        $userId = auth('api')->id();

        return match ($value) {
            'all' => $query,
            'featured' => $query->where('is_featured', true),
            'my-article' => $userId ? $query->where('user_id', $userId) : $query,
            'popular', 'most_liked', 'most_bookmarked' => $query,
            'most_viewed' => $query->orderByDesc(static::viewsCountSubquery()),
            default => $query,
        };
    }

    public function scopeSort($query, $value)
    {
        return match ($value) {
            'oldest' => $query->orderBy('published_at')->orderBy('created_at'),
            'popular', 'most_liked' => $query
                ->orderByDesc(static::likesCountSubquery())
                ->orderByDesc('published_at'),
            'most_viewed' => $query->orderByDesc(static::viewsCountSubquery())->orderByDesc('published_at'),
            'most_bookmarked' => $query
                ->orderByDesc(static::bookmarksCountSubquery())
                ->orderByDesc('published_at'),
            'reading_time_asc' => $query->orderBy('reading_time_minutes'),
            'reading_time_desc' => $query->orderByDesc('reading_time_minutes'),
            default => $query->orderByDesc('published_at')->orderByDesc('created_at'),
        };
    }

    protected static function likesCountSubquery()
    {
        return Like::query()
            ->selectRaw('count(*)')
            ->whereColumn('likeable_id', 'articles.id')
            ->where('likeable_type', static::class);
    }

    protected static function bookmarksCountSubquery()
    {
        return DB::table('bookmarks')
            ->selectRaw('count(*)')
            ->whereColumn('bookmarkable_id', 'articles.id')
            ->where('bookmarkable_type', static::class);
    }

    public static function viewsCountSubquery()
    {
        return DB::table('views')
            ->selectRaw('count(*)')
            ->whereColumn('viewable_id', 'articles.id')
            ->where('viewable_type', static::class);
    }

    public function scopeCategoryId($query, $categoryIds)
    {
        if (is_array($categoryIds) && ! empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        } elseif (! empty($categoryIds)) {
            $query->where('category_id', $categoryIds);
        }

        return $query;
    }

    public function scopeAuthorId($query, $authorId)
    {
        if ($authorId) {
            $query->where('user_id', $authorId);
        }

        return $query;
    }

    public function scopeWhereHasTagIds($query, array $tagIds)
    {
        if (empty($tagIds)) {
            return $query;
        }

        return $query->whereExists(function ($sub) use ($tagIds) {
            $sub->select(DB::raw(1))
                ->from('taggable_taggables')
                ->whereColumn('taggable_taggables.taggable_id', 'articles.id')
                ->where('taggable_taggables.taggable_type', static::class)
                ->whereIn('taggable_taggables.tag_id', $tagIds);
        });
    }

    public function scopeTag($query, $tagSlug)
    {
        if (! $tagSlug) {
            return $query;
        }

        $tag = Tag::findBySlug((string) $tagSlug);

        return $tag ? $query->whereHasTagIds([$tag->tag_id]) : $query;
    }

    public function isEditableBy($user): bool
    {
        if ($user && $this->user_id === $user->id) {
            return true;
        }

        return false;
    }

    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function ratings()
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function averageRating()
    {
        return $this->ratings()->avg('rating') ?: 0;
    }

    public function sumRating()
    {
        return $this->ratings()->sum('rating');
    }

    public function sumOfAllRate()
    {
        return $this->ratings()->count();
    }

    public function sumOfRateNumber($rateNumber = 5)
    {
        return $this->ratings()->where('rating', '=', $rateNumber)->count();
    }

    public function checkUserRateThis($user_id = null)
    {
        $userId = $user_id ?: auth('api')->id();
        if (! $userId) {
            return false;
        }

        return Rating::query()
            ->where('rateable_type', $this->getMorphClass())
            ->where('rateable_id', $this->id)
            ->where('user_id', $userId)
            ->first() ?: false;
    }

    public function viewCount(): int
    {
        if (isset($this->views_count)) {
            return (int) $this->views_count;
        }

        return (int) $this->views()->count();
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
