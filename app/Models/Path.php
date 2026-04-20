<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CascadesDeletes;
use Illuminate\Support\Facades\Storage;

class Path extends Model
{
    use HasFactory, Sluggable, CascadesDeletes;
    protected $fillable = [
        'title',
        'english_title',
        'slug',
        'description',
        'short_description',
        'meta_keywords',
        'icon',
        'poster',
        'trailer',
        'faqs',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'faqs' => 'array',
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'english_title',
                'onUpdate' => true,
            ]
        ];
    }

    public function videos()
    {
        return $this->morphMany(Video::class, 'videoable');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }


    public function courses()
    {
        return $this->belongsToMany(Course::class)->withTimestamps();
    }

    public function publishedCourses()
    {
        return $this->courses()->where('publish', true);
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

    public function automationRules()
    {
        return $this->hasMany(PathAutomationRule::class);
    }

    public function corequisites()
    {
        return $this->belongsToMany(
            Path::class,
            'path_relations',
            'path_id',
            'related_path_id'
        )->wherePivot('type', 'corequisite');
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

    public function getCascadeRelations(): array
    {
        return [
            'videos',
            'comments',
            'automationRules',
        ];
    }

    public function deleteMediaFiles()
    {
        foreach (['icon', 'poster', 'trailer'] as $field) {
            $url = $this->{$field};
            if (!$url) continue;
            foreach (config('filesystems.disks') as $disk => $config) {
                if (!isset($config['url'])) continue;
                $baseUrl = rtrim($config['url'], '/');
                if (str_starts_with($url, $baseUrl)) {
                    $relativePath = ltrim(str_replace($baseUrl, '', $url), '/');
                    if (Storage::disk($disk)->exists($relativePath)) {
                        Storage::disk($disk)->delete($relativePath);
                    }
                    break;
                }
            }
        }
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }
}
