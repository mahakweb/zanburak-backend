<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CascadesDeletes;
use Illuminate\Support\Facades\Storage;

class Level extends Model
{
    use HasFactory, Sluggable, CascadesDeletes;
    protected $fillable = [
        'title',
        'english_title',
        'slug',
        'description',
        'icon',
    ];


    public function getCascadeRelations(): array
    {
        return [];
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

    public function courses()
    {
        return $this->hasMany(Course::class);
    }


    public function deleteMediaFiles()
    {
        foreach (['icon'] as $field) {
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
}
