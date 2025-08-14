<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class MissionCategory extends Model
{
    use HasFactory, Sluggable;
    protected $fillable = [
        "title","english_title", "slug"
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'english_title'
            ]
        ];
    }

    public function missions()
    {
        return $this->hasMany(Mission::class, 'category_id');
    }
}
