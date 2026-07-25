<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationRolePermission extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'permission',
        'allowed',
    ];

    protected $casts = [
        'allowed' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
