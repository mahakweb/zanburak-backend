<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementItem extends Model
{
    protected $fillable = [
        'settlement_id',
        'payment_id',
        'payment_item_id',
        'teacher_id',
        'amount',
        'gross_amount',
        'platform_amount',
        'site_percent',
        'teacher_percent',
        'charged_amount',
        'active_payment_item_id',
        'released_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'gross_amount' => 'integer',
        'platform_amount' => 'integer',
        'site_percent' => 'float',
        'teacher_percent' => 'float',
        'charged_amount' => 'integer',
        'released_at' => 'datetime',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function paymentItem(): BelongsTo
    {
        return $this->belongsTo(PaymentItem::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
};
