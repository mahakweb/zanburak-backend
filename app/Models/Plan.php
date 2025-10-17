<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'english_title',
        'period_time',
        'price',
        'icon',
        'status',
        'popular',
        'description',
        'features',
    ];

    protected $casts = [
        'status' => 'boolean',
        'popular' => 'boolean',
        'features' => 'array',
    ];


    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot(["payment_id", "tracking_number", "price", "status", "started_at", "expired_at"]);
    }

    public function activeUsers()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(["payment_id", "tracking_number", "price", "status", "started_at", "expired_at"])
            ->wherePivot('expired_at', '>', now());
    }
}
