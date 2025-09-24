<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'user_id',
        'description',
        'amount',
        'after_balance',
        'type',
        'tracking_number',
        'payment_id',
        'reference_id'
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
