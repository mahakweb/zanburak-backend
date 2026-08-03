<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessengerOneTimePrekey extends Model
{
    protected $table = 'messenger_one_time_prekeys';

    protected $fillable = [
        'device_row_id',
        'prekey_id',
        'public_key',
        'signature',
        'consumed_at',
    ];

    protected $casts = [
        'prekey_id' => 'integer',
        'consumed_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(MessengerCryptoDevice::class, 'device_row_id');
    }
}
