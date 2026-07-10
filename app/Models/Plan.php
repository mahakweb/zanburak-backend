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
        'allows_installment',
        'icon',
        'status',
        'popular',
        'description',
        'features',
    ];

    protected $casts = [
        'status' => 'boolean',
        'popular' => 'boolean',
        'allows_installment' => 'boolean',
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

    public function scopeInstallment($query, $value)
    {
        if ($value === null || $value === '' || $value === 'all') {
            return $query;
        }

        if (in_array($value, [1, '1', true, 'yes', 'true'], true)) {
            return $query->where('allows_installment', true);
        }

        return $query;
    }
}
