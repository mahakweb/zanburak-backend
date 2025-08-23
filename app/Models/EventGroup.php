<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class EventGroup extends Model
{
    use HasFactory, Sluggable;
    protected $fillable = [
        'title',
        'english_title',
        'slug',
        'description',
        'icon'
    ];

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'english_title',
                'onUpdate' => true,
            ],
        ];
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }
}
