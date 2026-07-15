<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Restrict messages to rows that are currently visible to a user.
     *
     * This is the single authorization boundary for message bodies: the
     * conversation must be visible, its clear cutoff must be respected, and a
     * per-user deletion must not exist. SoftDeletes adds the global
     * messages.deleted_at predicate.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->whereNull('messages.deleted_at')
            ->whereExists(function ($pivot) use ($user) {
                $pivot->selectRaw('1')
                    ->from('conversation_user')
                    ->whereColumn('conversation_user.conversation_id', 'messages.conversation_id')
                    ->where('conversation_user.user_id', $user->id)
                    ->whereNull('conversation_user.deleted_at')
                    ->where(function ($cutoff) {
                        $cutoff->whereNull('conversation_user.cleared_at')
                            ->orWhereColumn('messages.created_at', '>', 'conversation_user.cleared_at');
                    });
            })
            ->whereNotExists(function ($deletions) use ($user) {
                $deletions->selectRaw('1')
                    ->from('message_deletions')
                    ->whereColumn('message_deletions.message_id', 'messages.id')
                    ->where('message_deletions.user_id', $user->id);
            });
    }

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
