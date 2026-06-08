<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessengerEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'conversation_id',
        'type',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
