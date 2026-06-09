<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'client_id',
        'body',
        'type',
        'reply_to_id',
        'reply_show_title',
        'forwarded_from_user_id',
        'read_at',
        'edited_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
        'reply_show_title' => 'boolean',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo()
    {
        return $this->belongsTo(Message::class, 'reply_to_id')->withTrashed();
    }

    public function forwardedFromUser()
    {
        return $this->belongsTo(User::class, 'forwarded_from_user_id');
    }

    /**
     * Users who have hidden this message from their own view ("delete for me").
     */
    public function deletedForUsers()
    {
        return $this->belongsToMany(User::class, 'message_deletions', 'message_id', 'user_id')->withTimestamps();
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->id;
    }
}
