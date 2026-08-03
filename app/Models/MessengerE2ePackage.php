<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessengerE2ePackage extends Model
{
    protected $table = 'messenger_e2e_packages';

    protected $fillable = [
        'conversation_id',
        'sender_user_id',
        'sender_device_id',
        'recipient_device_id',
        'recipient_user_id',
        'key_version',
        'ciphertext',
        'consumed_at',
    ];

    protected $casts = [
        'key_version' => 'integer',
        'consumed_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
