<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model
{
    use HasFactory;

    protected $casts = [
        'response' => 'array',
    ];


    protected $fillable = [
        'payment_id',
        'status',
        'attempt_reference',
        'message',
        'response',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
