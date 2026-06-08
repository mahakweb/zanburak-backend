<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'title', 'category_id', 'icon', 'description', 'is_active', 'levels', 'expired_at'];

    protected $casts = [
        'id' => 'string',
        'is_active' => 'boolean',
        'expired_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>=', now());
            });
    }

    public function category()
    {
        return $this->belongsTo(MissionCategory::class, 'category_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'mission_user')->withPivot('progress', 'completed_at')->withTimestamps();
    }
}
