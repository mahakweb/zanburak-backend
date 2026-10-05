<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'settlement_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
};
