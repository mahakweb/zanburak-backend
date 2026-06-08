<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'last_message_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withTimestamps()
            ->withPivot(['last_read_at', 'cleared_at', 'deleted_at', 'muted_at']);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function hasParticipant(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->users()->where('users.id', $user->id)->exists();
    }

    public function otherUser(User $me): ?User
    {
        return $this->users->firstWhere('id', '!=', $me->id);
    }

    /**
     * Number of unread (incoming) messages for a given user.
     */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}
