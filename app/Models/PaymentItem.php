<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'payable_type',
        'payable_id',
        'price',
        'discount_amount',
        'discount_code',
        'final_price',
        'gateway_fee_amount',
        'charged_price',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function payable()
    {
        return $this->morphTo();
    }
}
