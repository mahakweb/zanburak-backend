<?php

namespace App\Models;

use App\Contracts\Likeable;
use App\Models\Concerns\Likes;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentTaggable\Taggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'allowed_user_ids'
    ];

    protected $casts = [
        'allowed_user_ids' => 'array',
    ];
    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        // $array = $this->toArray();

        // unset($array['updated_at']);

        // return $array;
        return [
            'subject' => $this->subject,
            'question' => $this->question,
        ];
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
            'no-answer' => $query->withCount('answers')->having('answers_count', 0),
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

}
