<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class QuestionCategory extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'title',
        'english_title',
        'slug',
        'parent_id',
        'description',
        'icon',
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


    public function parent()
    {
        return $this->belongsTo(QuestionCategory::class);
    }

    public function children()
    {
        return $this->hasMany(QuestionCategory::class , 'parent');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
