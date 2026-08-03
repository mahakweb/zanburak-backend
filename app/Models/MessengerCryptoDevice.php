<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessengerCryptoDevice extends Model
{
    protected $table = 'messenger_crypto_devices';

    protected $fillable = [
        'user_id',
        'device_id',
        'label',
        'identity_public_key',
        'signed_prekey_id',
        'signed_prekey_public',
        'signed_prekey_signature',
        'last_seen_at',
        'revoked_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
        'signed_prekey_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function oneTimePrekeys(): HasMany
    {
        return $this->hasMany(MessengerOneTimePrekey::class, 'device_row_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at');
    }

    public function toPublicBundle(?array $oneTime = null): array
    {
        return [
            'user_id' => (int) $this->user_id,
            'device_id' => $this->device_id,
            'label' => $this->label,
            'identity_public_key' => $this->identity_public_key,
            'signed_prekey_id' => (int) $this->signed_prekey_id,
            'signed_prekey_public' => $this->signed_prekey_public,
            'signed_prekey_signature' => $this->signed_prekey_signature,
            'one_time_prekey' => $oneTime,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
        ];
    }
}
