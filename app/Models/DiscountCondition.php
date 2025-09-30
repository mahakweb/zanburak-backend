<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCondition extends Model
{
    protected $fillable = [
        'discount_id',
        'item_type',
        'condition_type',
        'operator',
        'value',
        'extra'
    ];

    protected $casts = [
        'extra' => 'array'
    ];

    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }
}
