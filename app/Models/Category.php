<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Cviebrock\EloquentTaggable\Taggable;

class Category extends Model
{
    use HasFactory, Sluggable, Taggable;
    protected $fillable = [
        'title',
        'english_title',
        'slug',
        'parent_id',
        'description',
        'icon',
        'status',
        'assignment_type',
        'match_type',
    ];

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::retrieved(function ($model) {
    //     });
    // }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'english_title',
                'onUpdate' => true,
            ]
        ];
    }

    public function scopeOrder($query, $value)
    {
        return match ($value) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }
    public function scopeStatus($query, $value)
    {
        return match ($value) {
            'active' => $query->where('status', '1'),
            'inactive' => $query->where('status', '0'),
            default => $query,
        };
    }


    public function automationRules()
    {
        return $this->hasMany(AutomationRule::class);
    }



    public function parent()
    {
        return $this->belongsTo(Category::class);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id'); // parent_id
    }

    public function course()
    {
        return $this->belongsToMany(Course::class);
    }

}
