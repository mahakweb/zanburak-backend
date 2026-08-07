<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessengerIdentityPackage extends Model
{
    protected $table = 'messenger_identity_packages';

    protected $fillable = [
        'sender_user_id',
        'sender_device_id',
        'recipient_user_id',
        'recipient_device_id',
        'ciphertext',
        'consumed_at',
    ];

    protected $casts = [
        'consumed_at' => 'datetime',
    ];
}
