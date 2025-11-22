<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;

class FaqCategory extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'title',
        'english_title',
        'slug',
        'icon',
        'order',
        'status',
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

    public function faqs()
    {
        return $this->hasMany(Faq::class, 'category_id')->orderBy('order');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
