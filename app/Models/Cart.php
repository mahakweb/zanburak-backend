<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'cartable_type',
        'cartable_id',
        'qty',
        'price',
        'discount_id',
        'discount_amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function discount()
    {
        return $this->belongsTo(Discount::class);
    }


    public function cartable()
    {
        return $this->morphTo();
    }
}
