<?php

namespace App\Models;

use Cviebrock\EloquentTaggable\Models\Tag as BaseTag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Overtrue\LaravelFollow\Traits\Followable;

class Tag extends BaseTag
{
    use Followable;

    public function getRouteKeyName(): string
    {
        return 'normalized';
    }

    public function questions(): MorphToMany
    {
        return $this->taggedModels(Question::class);
    }

    public function courses(): MorphToMany
    {
        return $this->taggedModels(Course::class);
    }

    public function articles(): MorphToMany
    {
        return $this->taggedModels(Article::class);
    }

    public function getSlugAttribute(): string
    {
        return $this->normalized;
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('normalized', mb_strtolower(trim($slug)))->first();
    }

    public function toApiArray(?User $user = null): array
    {
        $questionsCount = $this->questions()->where('publish', 1)->count();
        $coursesCount = $this->courses()->count();
        $articlesCount = $this->articles()->where('publish', 1)->where('status', 'published')->count();

        return [
            'id' => $this->tag_id,
            'name' => $this->name,
            'slug' => $this->normalized,
            'questions_count' => $questionsCount,
            'courses_count' => $coursesCount,
            'articles_count' => $articlesCount,
            'followers_count' => $this->followers()->count(),
            'is_following' => $user ? $user->isFollowing($this) : false,
        ];
    }
}
