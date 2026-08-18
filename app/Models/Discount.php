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
        'description',
        'type',
        'value',
        'max_discount_amount',
        'usage_limit',
        'per_user_limit',
        'starts_at',
        'ends_at',
        'is_active',
        'stackable',
        'apply_automatically',
        'is_public',
        'banner_title',
        'banner_description',
        'banner_icon',
        'cta_text',
        'destination_type',
        'destination_url',
        'priority',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'stackable' => 'boolean',
        'apply_automatically' => 'boolean',
        'is_public' => 'boolean',
        'max_discount_amount' => 'integer',
        'priority' => 'integer',
        'value' => 'integer',
        'usage_limit' => 'integer',
        'per_user_limit' => 'integer',
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

    public function remainingUsage(): ?int
    {
        if ($this->usage_limit === null) {
            return null;
        }

        $used = $this->usages_count ?? $this->usages()->count();

        return max(0, (int) $this->usage_limit - (int) $used);
    }

    public function isCurrentlyValid(?Carbon $at = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $at = $at ?: now();

        if ($this->starts_at && $this->starts_at->isAfter($at)) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isBefore($at)) {
            return false;
        }

        return true;
    }
}
