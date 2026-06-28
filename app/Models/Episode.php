<?php

namespace App\Models;

use App\Contracts\Likeable;
use App\Services\Search\SearchTermExtractor;
use App\Models\Concerns\Likes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Cviebrock\EloquentSluggable\Sluggable;
use Laravel\Scout\Searchable;
use LaravelInteraction\Bookmark\Concerns\Bookmarkable;
use App\Traits\CascadesDeletes;

class Episode extends Model implements Likeable
{
    use HasFactory, Searchable, Sluggable, Bookmarkable, Likes, CascadesDeletes;
    protected $fillable = [
        'section_id',
        'order',
        'title',
        'english_title',
        'description',
        'meta_keywords',
        'total_time',
        'lock',
        'publish_date',
        'publish',
    ];

    protected $casts = [
        'publish' => 'boolean',
        'lock' => 'boolean',
    ];

    public function getCascadeRelations(): array
    {
        return ['videos', 'comments', 'views', 'likes', 'bookmarkableBookmarks', 'attachs'];
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }



    /**
     * Get the indexable data array for the model.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing('section.course');
        $course = $this->section?->course;
        $extractor = app(SearchTermExtractor::class);

        $searchTerms = $extractor->fromDocument(
            title: $this->title,
            englishTitle: $this->english_title,
            metaKeywords: $this->meta_keywords,
            tags: [],
            categories: array_filter([$course?->title, $course?->english_title]),
        );

        return [
            'id' => $this->id,
            'title' => $this->title,
            'english_title' => $this->english_title,
            'slug' => $this->slug,
            'description' => $this->description,
            'meta_keywords' => $this->meta_keywords,
            'search_terms' => implode(' ', $searchTerms),
            'course_title' => $course?->title,
            'course_english_title' => $course?->english_title,
            'course_slug' => $course?->slug,
            'view_count' => $this->viewCount(),
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
                'source' => 'english_title',
                'onUpdate' => true,
            ]
        ];
    }


    public function views()
    {
        return $this->morphMany(View::class, 'viewable');
    }

    public function viewCount()
    {
        return $this->views()->count();
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }


    public function lock()
    {
        return !! $this->lock;
    }


    /**
     * Get all of the episode's attachs.
     */
    public function attachs(): MorphMany
    {
        return $this->morphMany(Attach::class, 'attachable');
    }

    public function quizzes(): MorphMany
    {
        return $this->morphMany(\App\Models\Quiz\Quiz::class, 'quizzable');
    }


    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function videos()
    {
        return $this->morphMany(Video::class, 'videoable');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
