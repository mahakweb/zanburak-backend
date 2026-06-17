<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Cviebrock\EloquentSluggable\Sluggable;
use App\Traits\CascadesDeletes;
use Illuminate\Support\Facades\Storage;

class Section extends Model
{
    use HasFactory, Sluggable, CascadesDeletes;
    protected $fillable = [
        'course_id',
        'title',
        'english_title',
        'description',
        'total_time',
        'start_date',
        'end_date',
        'status',
        'publish',
        'attached_file',
    ];

    protected $casts = [
        'publish' => 'boolean',
        'status' => 'boolean',
    ];

    public function getCascadeRelations(): array
    {
        return ['episode'];
    }

    public function deleteMediaFiles()
    {
        foreach (['attached_file'] as $field) {
            $url = $this->{$field};

            if (!$url) {
                continue;
            }

            foreach (config('filesystems.disks') as $disk => $config) {
                if (!isset($config['url'])) {
                    continue;
                }

                $baseUrl = rtrim($config['url'], '/');

                if (str_starts_with($url, $baseUrl)) {
                    $relativePath = ltrim(str_replace($baseUrl, '', $url), '/');
                    Storage::disk($disk)->delete($relativePath);
                    break;
                }
            }
        }
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



    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function episode()
    {
        return $this->hasMany(Episode::class);
    }

    public function totalTime()
    {
        return $this->episode->sum('total_time');
    }

    public function quizzes(): MorphMany
    {
        return $this->morphMany(\App\Models\Quiz\Quiz::class, 'quizzable');
    }
}
