<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'driver',
        'gateway_variant',
        'digipay_mode',
        'digipay_preferred_gateway',
        'payment_method',
        'resnumber',
        'amount',
        'base_amount',
        'gateway_fee_amount',
        'gateway_fee_percent',
        'wallet_paid_amount',
        'gateway_paid_amount',
        'digipay_credit_amount',
        'digipay_cash_amount',
        'digipay_verify_payload',
        'discount_amount',
        'discount_code',
        'status',
        'tracking_number',
        'visited_at',
        'reference_id',
        'uuid',
        'paid_at',
        'expired_at',
        'verified_by_admin',
        'description',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'visited_at' => 'datetime',
        'expired_at' => 'datetime',
        'status' => 'boolean',
        'digipay_verify_payload' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            if (empty($model->reference_id)) {
                $model->reference_id = 'REF-'
                    . now()->format('YmdHis')   
                    . '-' . strtoupper(Str::random(6));
            }

            if (empty($model->resnumber)) {
                $model->resnumber = $model->reference_id;
            }
        });
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function items()
    {
        return $this->hasMany(PaymentItem::class);
    }


    public function attempts()
    {
        return $this->hasMany(PaymentAttempt::class);
    }

    public function isPaid(): bool
    {
        return $this->paid_at && $this->status;
    }

    public function isExpired(): bool
    {
        return $this->expired_at && now()->greaterThan($this->expired_at);
    }

    public function canRetry(): bool
    {
        return !$this->status && !$this->paid_at && $this->expired_at && $this->expired_at->greaterThan(now());
    }

    public function scopeForReportedMethod($query, ?string $method)
    {
        if (!$method || $method === 'all') {
            return $query;
        }

        if ($method === 'bank') {
            return $query->whereIn('payment_method', ['bank', 'wallet_bank']);
        }

        if ($method === 'wallet') {
            return $query->where(function ($inner) {
                $inner->where('payment_method', 'wallet')
                    ->orWhere(function ($split) {
                        $split->where('payment_method', 'wallet_bank')
                            ->where('wallet_paid_amount', '>', 0);
                    });
            });
        }

        return $query->where('payment_method', $method);
    }
}
