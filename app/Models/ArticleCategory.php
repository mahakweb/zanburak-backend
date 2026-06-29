<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleCategory extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'title',
        'english_title',
        'slug',
        'parent_id',
        'description',
        'icon',
        'order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'order' => 'integer',
    ];

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

    public function articles()
    {
        return $this->hasMany(Article::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('title');
    }
}
