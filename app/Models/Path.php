<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Path extends Model
{
    use HasFactory, Sluggable;
    protected $fillable = [
        'title',
        'english_title',
        'slug',
        'description',
        'icon',
        'poster',
        'trailer',
        'faqs',
        'status',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'english_title'
            ]
        ];
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }


    public function courses()
    {
        return $this->belongsToMany(Course::class)->withTimestamps();
    }

    public function prerequisites()
    {
        return $this->belongsToMany(
            Path::class,
            'path_relations',
            'path_id',
            'related_path_id'
        )->wherePivot('type', 'prerequisite');
    }

    public function nextSteps()
    {
        return $this->belongsToMany(
            Path::class,
            'path_relations',
            'path_id',
            'related_path_id'
        )->wherePivot('type', 'next');
    }

    public function prerequisiteFor()
    {
        return $this->belongsToMany(
            Path::class,
            'path_relations',
            'related_path_id',
            'path_id'
        )->wherePivot('type', 'prerequisite');
    }

    public function previousSteps()
    {
        return $this->belongsToMany(
            Path::class,
            'path_relations',
            'related_path_id',
            'path_id'
        )->wherePivot('type', 'next');
    }
}
