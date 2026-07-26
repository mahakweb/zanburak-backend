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

    public const TYPE_PRIVATE = 'private';

    public const TYPE_SAVED = 'saved';

    public const TYPE_GROUP = 'group';

    public const TYPE_CHANNEL = 'channel';

    protected $fillable = [
        'type',
        'title',
        'description',
        'avatar',
        'cover',
        'username',
        'is_public',
        'owner_id',
        'member_count',
        'message_count',
        'is_verified',
        'is_archived',
        'slow_mode_seconds',
        'history_visible',
        'join_approval_required',
        'reactions_enabled',
        'comments_enabled',
        'signatures_enabled',
        'who_can_send',
        'who_can_invite',
        'who_can_pin',
        'who_can_edit_info',
        'messages_locked',
        'messages_locked_until',
        'lock_schedule',
        'last_message_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'is_public' => 'boolean',
        'is_verified' => 'boolean',
        'is_archived' => 'boolean',
        'history_visible' => 'boolean',
        'join_approval_required' => 'boolean',
        'reactions_enabled' => 'boolean',
        'comments_enabled' => 'boolean',
        'signatures_enabled' => 'boolean',
        'messages_locked' => 'boolean',
        'messages_locked_until' => 'datetime',
        'lock_schedule' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withTimestamps()
            ->withPivot([
                'role', 'nickname', 'custom_title', 'last_read_at', 'last_read_message_id',
                'cleared_at', 'deleted_at', 'muted_at', 'muted_until', 'is_banned', 'is_active',
                'joined_at', 'notification_mode', 'message_count', 'badges',
            ]);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function invites(): HasMany
    {
        return $this->hasMany(ConversationInvite::class);
    }

    public function joinRequests(): HasMany
    {
        return $this->hasMany(ConversationJoinRequest::class);
    }

    public function bans(): HasMany
    {
        return $this->hasMany(ConversationBan::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ConversationAuditLog::class);
    }

    public function rolePermissions(): HasMany
    {
        return $this->hasMany(ConversationRolePermission::class);
    }

    public function isGroup(): bool
    {
        return $this->type === self::TYPE_GROUP;
    }

    public function isChannel(): bool
    {
        return $this->type === self::TYPE_CHANNEL;
    }

    public function isCommunity(): bool
    {
        return $this->isGroup() || $this->isChannel();
    }

    /**
     * Whether regular members are currently blocked from sending.
     * Owner/admin/moderator bypass this in permission checks.
     */
    public function isChatLockedNow(?\DateTimeInterface $at = null): bool
    {
        if (! $this->isCommunity()) {
            return false;
        }

        $now = $at
            ? \Illuminate\Support\Carbon::parse($at->format('c'))
            : now();

        if ($this->messages_locked) {
            return true;
        }

        if ($this->messages_locked_until && $this->messages_locked_until->isFuture()) {
            return true;
        }

        $schedule = $this->lock_schedule;
        if (! is_array($schedule) || empty($schedule['enabled'])) {
            return false;
        }

        $tz = $schedule['timezone'] ?? config('app.timezone', 'UTC');
        try {
            $local = $now->copy()->timezone($tz);
        } catch (\Throwable $e) {
            $local = $now->copy();
        }

        $start = $this->normalizeScheduleTime($schedule['start'] ?? null);
        $end = $this->normalizeScheduleTime($schedule['end'] ?? null);
        if (! $start || ! $end) {
            return false;
        }

        $days = $schedule['days'] ?? null;
        if (is_array($days) && $days !== [] && ! in_array((int) $local->dayOfWeek, array_map('intval', $days), true)) {
            return false;
        }

        $current = $local->format('H:i');
        if ($start <= $end) {
            return $current >= $start && $current < $end;
        }

        // Overnight window (e.g. 22:00 → 08:00)
        return $current >= $start || $current < $end;
    }

    /** Normalize "9:5" / "09:05:00" → "09:05" for safe string compare. */
    protected function normalizeScheduleTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! preg_match('/^(\d{1,2}):(\d{1,2})/', trim((string) $value), $m)) {
            return null;
        }
        $h = max(0, min(23, (int) $m[1]));
        $min = max(0, min(59, (int) $m[2]));

        return sprintf('%02d:%02d', $h, $min);
    }

    public function hasParticipant(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        $query = $this->users()
            ->where('users.id', $user->id)
            ->whereNull('conversation_user.deleted_at');

        // Membership flags apply to groups/channels; private/saved stay simple.
        // Treat NULL flags as active/not-banned (legacy rows).
        if ($this->isCommunity()) {
            $query->where(function ($q) {
                $q->where('conversation_user.is_active', true)
                    ->orWhereNull('conversation_user.is_active');
            })->where(function ($q) {
                $q->where('conversation_user.is_banned', false)
                    ->orWhereNull('conversation_user.is_banned');
            });
        }

        return $query->exists();
    }

    public function memberPivot(?User $user): ?object
    {
        if ($user === null) {
            return null;
        }

        if ($this->relationLoaded('users')) {
            return $this->users->firstWhere('id', $user->id)?->pivot;
        }

        return $this->users()
            ->where('users.id', $user->id)
            ->first()
            ?->pivot;
    }

    public function memberRole(?User $user): ?string
    {
        return $this->memberPivot($user)?->role;
    }

    public function otherUser(User $me): ?User
    {
        if ($this->isCommunity()) {
            return null;
        }

        return $this->users->firstWhere('id', '!=', $me->id);
    }

    /**
     * Number of unread (incoming) messages for a given user.
     */
    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->visibleTo($user)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Attach a response-only value that must never be written to the DB.
     * (Eloquent would otherwise treat it as a dirty column on the next save/update.)
     */
    public function setResponseAttribute(string $key, mixed $value): static
    {
        $this->setAttribute($key, $value);
        $this->syncOriginalAttribute($key);

        return $this;
    }

    public function displayTitle(?User $viewer = null): ?string
    {
        if ($this->isCommunity()) {
            return $this->title;
        }

        if ($this->type === self::TYPE_SAVED) {
            return 'Saved Messages';
        }

        if ($viewer && $this->relationLoaded('users')) {
            $other = $this->otherUser($viewer);

            return $other
                ? trim("{$other->first_name} {$other->last_name}") ?: $other->username
                : null;
        }

        return null;
    }
}
