<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessengerE2eKeyVault extends Model
{
    protected $table = 'messenger_e2e_key_vault';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'key_version',
        'sender_device_id',
        'ciphertext',
    ];

    protected $casts = [
        'key_version' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
