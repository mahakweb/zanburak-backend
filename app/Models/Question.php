<?php

namespace App\Models;

use App\Contracts\Likeable;
use App\Services\Search\SearchTermExtractor;
use App\Models\Concerns\Likes;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentTaggable\Taggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LaravelInteraction\Bookmark\Concerns\Bookmarkable;
use Laravel\Scout\Searchable;
use Mehradsadeghi\FilterQueryString\FilterQueryString;

class Question extends Model implements Likeable
{
    use HasFactory, Searchable, Sluggable, FilterQueryString, Taggable, Bookmarkable, Likes;

    protected $filters = ['category_id', 'filter'];

    protected $fillable = [
        'user_id',
        'category_id',
        'subject',
        'question',
        'meta_keywords',
        'best_answer',
        'is_private',
        'allowed_user_ids',
        'publish'
    ];

    protected $casts = [
        'allowed_user_ids' => 'array',
        'publish' => 'boolean',
        'is_private' => 'boolean',
    ];
    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['tags', 'category']);
        $extractor = app(SearchTermExtractor::class);

        $searchTerms = $extractor->fromDocument(
            title: $this->subject,
            englishTitle: null,
            metaKeywords: $this->meta_keywords,
            tags: $this->tags->pluck('name')->all(),
            categories: array_filter([$this->category?->title]),
        );
        $searchTerms = $extractor->uniqueTerms([
            ...$searchTerms,
            ...$extractor->extractFromText($this->question),
        ]);

        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'question' => $this->question,
            'slug' => $this->slug,
            'meta_keywords' => $this->meta_keywords,
            'search_terms' => implode(' ', $searchTerms),
            'tags_text' => $this->tags->pluck('name')->join(' '),
            'category_title' => $this->category?->title,
            'answers_count' => $this->answers()->count(),
            'publish' => (bool) $this->publish,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return (bool) $this->publish;
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'subject',
                'onUpdate' => true,
            ],
        ];
    }


    public function scopeSearch($query, $searchKey)
    {
        if ($searchKey) {
            return $query->whereIn('id', Question::search($searchKey)->keys());
        }
        return $query;
    }

    public function scopeFilter($query, $value)
    {
        $user = auth('api')->user();
        $userId = auth('api')->id();

        return match ($value) {
            'all' => $query,
            'no-answer' => $query->withCount('answers')->doesntHave('answers'),
            'no-best-answer' => $query->whereNull('best_answer'),
            'best-answer' => $query->whereNotNull('best_answer'),
            'my-question' => $userId ? $query->where('user_id', $userId) : $query,
            'contributed_to' => $userId ? $query->whereHas('answers', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }) : $query,
            'private_discuss' => $userId ? $query->whereIn('id', $user->accessiblePrivateQuestions()->pluck('id')) : $query,
            default => $query,
        };
    }

    public function scopeCategoryId($query, $categoryIds)
    {
        if (is_array($categoryIds) && !empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        } elseif (!empty($categoryIds)) {
            $query->where('category_id', $categoryIds);
        }

        return $query;
    }

    public function isEditableBy($user)
    {
        if ($user && $this->user_id === $user->id) {
            $oneMonthAgo = now()->subMonth();
            return $this->created_at >= $oneMonthAgo;
        }
        return false;
    }

    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function viewCount()
    {
        return $this->views()->count();
    }


    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function latestAnswer()
    {
        return $this->hasOne(Answer::class)->latestOfMany();
    }

    public function bestAnswer()
    {
        return is_null($this->best_answer) ? false : Answer::find($this->best_answer);
    }

    public function category()
    {
        return $this->belongsTo(QuestionCategory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function scopeWhereHasTagIds($query, array $tagIds)
    {
        if (empty($tagIds)) {
            return $query;
        }

        return $query->whereExists(function ($sub) use ($tagIds) {
            $sub->select(DB::raw(1))
                ->from('taggable_taggables')
                ->whereColumn('taggable_taggables.taggable_id', 'questions.id')
                ->where('taggable_taggables.taggable_type', static::class)
                ->whereIn('taggable_taggables.tag_id', $tagIds);
        });
    }

}
