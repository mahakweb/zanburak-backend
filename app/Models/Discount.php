<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'title',
        'type',
        'value',
        'usage_limit',
        'per_user_limit',
        'starts_at',
        'ends_at',
        'is_active'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'boolean',
    ];

    public function eligibilities()
    {
        return $this->hasMany(DiscountEligibility::class);
    }

    public function usages()
    {
        return $this->hasMany(DiscountUsage::class);
    }

    public function conditions()
    {
        return $this->hasMany(DiscountCondition::class);
    }
}
