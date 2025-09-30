<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountEligibility extends Model
{
    protected $fillable = [
        'discount_id',
        'type',
        'target_type',
        'target_id'
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }
}
