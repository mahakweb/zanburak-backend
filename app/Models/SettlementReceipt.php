<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementReceipt extends Model
{
    protected $fillable = [
        'settlement_id',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'uploaded_by',
        'replaced_at',
        'deleted_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'replaced_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
};
