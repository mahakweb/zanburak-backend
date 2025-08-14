<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
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
                'source' => 'english_title'
            ]
        ];
    }


    public function course()
    {
        return $this->hasMany(Course ::class);
    }

}
