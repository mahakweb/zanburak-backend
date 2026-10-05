<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Settlement extends Model
{
    protected $fillable = [
        'uuid',
        'teacher_id',
        'amount',
        'payment_count',
        'status',
        'tracking_number',
        'iban',
        'account_holder',
        'payout_kind',
        'payout_number',
        'payout_bank_code',
        'user_bank_account_id',
        'description',
        'idempotency_key',
        'created_by',
        'paid_by',
        'paid_at',
        'current_receipt_id',
        'active_covered_payment_id',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'integer',
        'payment_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Settlement $settlement) {
            if (empty($settlement->uuid)) {
                $settlement->uuid = (string) Str::uuid();
            }
        });
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SettlementItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(SettlementReceipt::class);
    }

    public function currentReceipt(): BelongsTo
    {
        return $this->belongsTo(SettlementReceipt::class, 'current_receipt_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(SettlementAudit::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
};
