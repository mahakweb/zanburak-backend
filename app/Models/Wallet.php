<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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


    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->reference_id)) {
                $model->reference_id = 'WALLET-'
                    . now()->format('YmdHis')   
                    . '-' . strtoupper(Str::random(6));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
