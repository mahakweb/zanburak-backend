<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'type',
        'payment_info',
        'driver',
        'resnumber',
        'amount',
        'discount',
        'status',
        'tracking_number',
        'visited_at',
        'reference_id'
    ];


    public function user(){
        return $this->belongsTo(User::class);
    }
}
