<?php

namespace App\Services\Messenger;

use App\Models\Conversation;
use App\Models\ConversationAuditLog;
use App\Models\ConversationBan;
use App\Models\ConversationInvite;
use App\Models\ConversationJoinRequest;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\MessageView;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Groups & Channels domain logic layered on top of Messenger conversations.
 */
class GroupChannelService
{
    public function __construct(
        protected MessengerService $messenger,
        protected GroupPermissionService $permissions
    ) {}

    // -------------------------------------------------------------------------
    // Create
    // -------------------------------------------------------------------------

    public function createGroup(User $owner, array $data, array $memberIds = []): Conversation
    {
        return $this->createCommunity($owner, Conversation::TYPE_GROUP, $data, $memberIds, 'member');
    }

    public function createChannel(User $owner, array $data, array $memberIds = []): Conversation
    {
        return $this->createCommunity($owner, Conversation::TYPE_CHANNEL, $data, $memberIds, 'subscriber');
    }

    protected function createCommunity(
        User $owner,
        string $type,
        array $data,
        array $memberIds,
        string $defaultMemberRole
    ): Conversation {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Title is required');
        }

        $maxTitle = config('messenger_groups.max_title_length', 128);
        if (mb_strlen($title) > $maxTitle) {
            throw new \InvalidArgumentException("Title exceeds {$maxTitle} characters");
        }

        $isPublic = (bool) ($data['is_public'] ?? false);
        $username = isset($data['username']) ? $this->normalizeUsername($data['username']) : null;

        if ($isPublic) {
            if (! $username) {
                throw new \InvalidArgumentException('Public groups/channels require a username');
            }
            $this->assertUsernameAvailable($username);
        } else {
            // Private: Telegram-style auto invite id when none provided
            $username = $username ?: $this->generatePrivateUsername($type);
            $this->assertUsernameAvailable($username);
        }

        $memberIds = collect($memberIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id !== $owner->id)
            ->unique()
            ->values();

        $maxMembers = config('messenger_groups.max_members_per_create', 200);
        if ($memberIds->count() > $maxMembers) {
            throw new \InvalidArgumentException("Cannot add more than {$maxMembers} members at creation");
        }

        $users = User::whereIn('id', $memberIds)->where('active', true)->pluck('id');
        if ($users->count() !== $memberIds->count()) {
            throw new \InvalidArgumentException('One or more members were not found');
        }

        $conversation = DB::transaction(function () use ($owner, $type, $data, $title, $username, $memberIds, $defaultMemberRole, $isPublic) {
            $conversation = Conversation::create([
                'type' => $type,
                'title' => $title,
                'description' => $data['description'] ?? null,
                'avatar' => $data['avatar'] ?? null,
                'cover' => $data['cover'] ?? null,
                'username' => $username,
                'is_public' => $isPublic,
                'owner_id' => $owner->id,
                'member_count' => 1 + $memberIds->count(),
                'message_count' => 0,
                'history_visible' => (bool) ($data['history_visible'] ?? true),
                'join_approval_required' => (bool) ($data['join_approval_required'] ?? false),
                'reactions_enabled' => (bool) ($data['reactions_enabled'] ?? true),
                'who_can_send' => $type === Conversation::TYPE_CHANNEL ? 'admins' : ($data['who_can_send'] ?? 'all'),
                'who_can_invite' => $data['who_can_invite'] ?? 'admins',
                'who_can_pin' => $data['who_can_pin'] ?? 'admins',
                'who_can_edit_info' => $data['who_can_edit_info'] ?? 'admins',
            ]);

            $now = now();
            $historyVisible = (bool) ($data['history_visible'] ?? true);
            $clearedAt = $historyVisible ? null : $now;

            $attach = [
                $owner->id => [
                    'role' => 'owner',
                    'joined_at' => $now,
                    'is_active' => true,
                    'is_banned' => false,
                    'cleared_at' => null,
                    'badges' => json_encode(['owner']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            foreach ($memberIds as $uid) {
                $attach[$uid] = [
                    'role' => $defaultMemberRole,
                    'joined_at' => $now,
                    'is_active' => true,
                    'is_banned' => false,
                    'cleared_at' => $clearedAt,
                    'badges' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $conversation->users()->attach($attach);

            $this->audit($conversation, $owner, $type === Conversation::TYPE_CHANNEL ? 'channel.created' : 'group.created', null, [
                'title' => $title,
            ]);

            $this->postSystemEvent($conversation, $owner, 'created', [
                'title' => $title,
            ]);

            return $conversation;
        });

        $this->broadcastConversationUpdated($conversation);

        return $this->loadCommunity($conversation, $owner);
    }

    // -------------------------------------------------------------------------
    // Update / settings
    // -------------------------------------------------------------------------

    public function updateInfo(User $user, Conversation $conversation, array $data): Conversation
    {
        $this->assertCommunity($conversation);
        $this->messengerAssertParticipant($user, $conversation);

        $needsInfo = array_intersect_key($data, array_flip(['title', 'description']));
        $needsAvatar = array_key_exists('avatar', $data);
        $needsCover = array_key_exists('cover', $data);
        $needsUsername = array_key_exists('username', $data);

        if ($needsInfo) {
            // who_can_edit_info=all → any active member may change title/description.
            // otherwise require manage_group_info (admins by default).
            if (($conversation->who_can_edit_info ?? 'admins') !== 'all') {
                $this->permissions->assertCan($user, $conversation, 'manage_group_info');
            }
        }
        if ($needsAvatar) {
            $this->permissions->assertCan($user, $conversation, 'manage_avatar');
        }
        if ($needsCover) {
            $this->permissions->assertCan($user, $conversation, 'manage_cover');
        }
        if ($needsUsername) {
            $this->permissions->assertCan($user, $conversation, 'manage_username');
        }

        $updates = [];
        $events = [];

        if (isset($data['title'])) {
            $title = trim((string) $data['title']);
            if ($title === '') {
                throw new \InvalidArgumentException('Title cannot be empty');
            }
            if ($title !== $conversation->title) {
                $updates['title'] = $title;
                $events[] = ['group_name_changed', ['title' => $title]];
            }
        }

        if (array_key_exists('description', $data)) {
            $updates['description'] = $data['description'];
        }

        if ($needsAvatar && ($data['avatar'] ?? null) !== $conversation->avatar) {
            $updates['avatar'] = $data['avatar'];
            $events[] = ['group_photo_changed', []];
        }

        if ($needsCover) {
            $updates['cover'] = $data['cover'];
        }

        if ($needsUsername) {
            $username = $data['username'] ? $this->normalizeUsername($data['username']) : null;
            if ($username && $username !== $conversation->username) {
                $this->assertUsernameAvailable($username, $conversation->id);
            }
            $updates['username'] = $username;
        }

        foreach ([
            'history_visible', 'join_approval_required', 'reactions_enabled',
            'comments_enabled', 'signatures_enabled', 'who_can_send', 'who_can_invite', 'who_can_pin', 'who_can_edit_info',
            'is_archived',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $this->permissions->assertCan($user, $conversation, 'manage_group_info');
                $updates[$field] = $data[$field];
            }
        }

        if (array_key_exists('is_public', $data)) {
            $this->permissions->assertCan($user, $conversation, 'manage_group_info');
            $willBePublic = (bool) $data['is_public'];
            $updates['is_public'] = $willBePublic;

            $effectiveUsername = array_key_exists('username', $updates)
                ? $updates['username']
                : ($conversation->username);

            if ($willBePublic && ! $effectiveUsername) {
                throw new \InvalidArgumentException('Public groups/channels require a username');
            }

            if (! $willBePublic && ! $effectiveUsername) {
                $updates['username'] = $this->generatePrivateUsername($conversation->type);
            }
        }

        // If becoming public via username alone while already public, fine;
        // if username cleared while public, reject.
        $finalPublic = array_key_exists('is_public', $updates)
            ? (bool) $updates['is_public']
            : (bool) $conversation->is_public;
        $finalUsername = array_key_exists('username', $updates)
            ? $updates['username']
            : $conversation->username;
        if ($finalPublic && ! $finalUsername) {
            throw new \InvalidArgumentException('Public groups/channels require a username');
        }

        if (array_key_exists('slow_mode_seconds', $data)) {
            $this->permissions->assertCan($user, $conversation, 'manage_slow_mode');
            $seconds = (int) $data['slow_mode_seconds'];
            if ($seconds < 0) {
                throw new \InvalidArgumentException('Invalid slow mode value');
            }
            $updates['slow_mode_seconds'] = $seconds;
        }

        // Chat lock fields
        if (array_key_exists('messages_locked', $data)
            || array_key_exists('messages_locked_until', $data)
            || array_key_exists('lock_schedule', $data)
        ) {
            $this->permissions->assertCan($user, $conversation, 'manage_slow_mode');
        }

        if (array_key_exists('messages_locked', $data)) {
            $updates['messages_locked'] = (bool) $data['messages_locked'];
            if ($updates['messages_locked']) {
                $events[] = ['chat_locked', []];
            } else {
                $updates['messages_locked_until'] = null;
                $events[] = ['chat_unlocked', []];
            }
        }

        if (array_key_exists('messages_locked_until', $data)) {
            $until = $data['messages_locked_until'];
            if ($until === null || $until === '') {
                $updates['messages_locked_until'] = null;
            } else {
                $parsed = Carbon::parse($until);
                if ($parsed->isPast()) {
                    throw new \InvalidArgumentException('Lock until must be in the future');
                }
                $updates['messages_locked_until'] = $parsed;
                $updates['messages_locked'] = false; // temporary lock uses until, not permanent flag
                $events[] = ['chat_locked', ['until' => $parsed->toIso8601String()]];
            }
        }

        if (array_key_exists('lock_schedule', $data)) {
            $schedule = $data['lock_schedule'];
            if ($schedule === null || $schedule === false) {
                $updates['lock_schedule'] = null;
            } elseif (is_array($schedule)) {
                $updates['lock_schedule'] = [
                    'enabled' => (bool) ($schedule['enabled'] ?? false),
                    'start' => $this->normalizeLockHm($schedule['start'] ?? '22:00') ?? '22:00',
                    'end' => $this->normalizeLockHm($schedule['end'] ?? '08:00') ?? '08:00',
                    'timezone' => $schedule['timezone'] ?? 'Asia/Tehran',
                    'days' => $schedule['days'] ?? null,
                ];
            } else {
                throw new \InvalidArgumentException('Invalid lock schedule');
            }
        }

        if ($updates) {
            $conversation->update($updates);
            $this->audit($conversation, $user, 'info.updated', null, $updates);
        }

        foreach ($events as [$event, $meta]) {
            $this->postSystemEvent($conversation, $user, $event, $meta);
        }

        $conversation = $this->loadCommunity($conversation->fresh(), $user);

        $this->broadcastConversationUpdated($conversation);

        return $conversation;
    }

    /**
     * Add members by user ids (invite permission required).
     *
     * @param  int[]  $userIds
     */
    public function addMembers(User $actor, Conversation $conversation, array $userIds): Conversation
    {
        $this->assertCommunity($conversation);
        $this->permissions->assertCan($actor, $conversation, 'invite_members');
        $this->assertWhoCanStaffGate($actor, $conversation, $conversation->who_can_invite);

        $ids = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== (int) $actor->id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            throw new \InvalidArgumentException('No members to add');
        }

        $users = User::whereIn('id', $ids)->where('active', true)->get();
        if ($users->count() !== $ids->count()) {
            throw new \InvalidArgumentException('One or more users were not found');
        }

        $role = $conversation->isChannel() ? 'subscriber' : 'member';

        foreach ($users as $target) {
            if ($this->isActivelyBanned($conversation, $target)) {
                continue;
            }
            if ($conversation->hasParticipant($target)) {
                continue;
            }
            $this->attachMember($conversation, $target, $role);
            $this->postSystemEvent($conversation, $target, 'user_joined', [
                'target_id' => $target->id,
                'target_name' => $this->userDisplayName($target),
            ]);
            $this->broadcastToMembers($conversation, 0, 'member.joined', [
                'conversation_id' => $conversation->id,
                'user_id' => $target->id,
            ]);
        }

        $conversation = $this->loadCommunity($conversation->fresh(), $actor);
        $this->broadcastConversationUpdated($conversation);

        return $conversation;
    }

    public function setChatLock(User $user, Conversation $conversation, array $data): Conversation
    {
        return $this->updateInfo($user, $conversation, array_intersect_key($data, array_flip([
            'messages_locked', 'messages_locked_until', 'lock_schedule',
        ])));
    }

    public function uploadImage(User $user, Conversation $conversation, $file, string $kind): Conversation
    {
        $this->assertCommunity($conversation);
        $permission = $kind === 'cover' ? 'manage_cover' : 'manage_avatar';
        $this->permissions->assertCan($user, $conversation, $permission);

        $folder = $kind === 'cover' ? 'images/messenger/covers' : 'images/messenger/avatars';
        $path = Storage::disk('static')->putFile($folder.'/'.date('Y/m/d'), $file);
        $url = rtrim(config('filesystems.disks.static.url', ''), '/').'/'.ltrim($path, '/');

        return $this->updateInfo($user, $conversation, [$kind === 'cover' ? 'cover' : 'avatar' => $url]);
    }

    // -------------------------------------------------------------------------
    // Membership
    // -------------------------------------------------------------------------

    public function listMembers(User $user, Conversation $conversation, ?string $role = null, int $perPage = 50): LengthAwarePaginator
    {
        $this->assertCommunity($conversation);
        $this->messengerAssertParticipant($user, $conversation);

        return $conversation->users()
            ->whereNull('conversation_user.deleted_at')
            ->where('conversation_user.is_active', true)
            ->when($role, fn ($q) => $q->where('conversation_user.role', $role))
            ->with('messengerSettings')
            ->orderByRaw("CASE conversation_user.role
                WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 WHEN 'moderator' THEN 2
                WHEN 'member' THEN 3 WHEN 'subscriber' THEN 3 ELSE 4 END")
            ->orderBy('conversation_user.joined_at')
            ->paginate($perPage);
    }

    public function memberProfile(User $viewer, Conversation $conversation, int $userId): array
    {
        $this->assertCommunity($conversation);
        $this->messengerAssertParticipant($viewer, $conversation);

        $member = $conversation->users()
            ->where('users.id', $userId)
            ->whereNull('conversation_user.deleted_at')
            ->firstOrFail();

        $pivot = $member->pivot;

        return [
            'user' => [
                'id' => $member->id,
                'first_name' => $member->first_name,
                'last_name' => $member->last_name,
                'username' => $member->username,
                'profile_pic' => $member->profile_pic,
            ],
            'nickname' => $pivot->nickname,
            'custom_title' => $pivot->custom_title,
            'role' => $pivot->role,
            'joined_at' => $pivot->joined_at,
            'message_count' => (int) $pivot->message_count,
            'badges' => $this->decodeBadges($pivot->badges),
            'is_banned' => (bool) $pivot->is_banned,
            'muted_until' => $pivot->muted_until,
        ];
    }

    public function updateMember(User $actor, Conversation $conversation, int $userId, array $data): array
    {
        $this->assertCommunity($conversation);
        $this->messengerAssertParticipant($actor, $conversation);

        $target = User::findOrFail($userId);
        if (! $conversation->hasParticipant($target) && ! $this->hasMembershipRow($conversation, $target)) {
            throw new \RuntimeException('Member not found');
        }

        $updates = [];

        if (array_key_exists('nickname', $data)) {
            if ((int) $actor->id !== (int) $userId) {
                $this->permissions->assertCan($actor, $conversation, 'manage_roles');
            }
            $updates['nickname'] = $data['nickname'];
        }

        if (array_key_exists('custom_title', $data)) {
            $this->permissions->assertCan($actor, $conversation, 'manage_roles');
            if (! $this->permissions->canModerate($actor, $conversation, $target) && (int) $actor->id !== (int) $userId) {
                throw new \RuntimeException('Forbidden');
            }
            $updates['custom_title'] = $data['custom_title'];
        }

        if (array_key_exists('notification_mode', $data) && (int) $actor->id === (int) $userId) {
            $mode = $data['notification_mode'];
            if (! in_array($mode, ['all', 'mentions', 'mute', 'custom'], true)) {
                throw new \InvalidArgumentException('Invalid notification mode');
            }
            $updates['notification_mode'] = $mode;
        }

        if (isset($data['role'])) {
            $this->changeRole($actor, $conversation, $target, $data['role']);
        }

        if ($updates) {
            $conversation->users()->updateExistingPivot($userId, $updates);
        }

        return $this->memberProfile($actor, $conversation, $userId);
    }

    public function changeRole(User $actor, Conversation $conversation, User $target, string $role): void
    {
        $this->permissions->assertCan($actor, $conversation, 'manage_roles');

        $roles = config('messenger_groups.roles.'.($conversation->isChannel() ? 'channel' : 'group'), []);
        if (! in_array($role, $roles, true) || $role === 'owner') {
            throw new \InvalidArgumentException('Invalid role');
        }

        if (! $this->permissions->canModerate($actor, $conversation, $target)) {
            throw new \RuntimeException('Forbidden');
        }

        $oldRole = $conversation->memberRole($target);
        $badges = $this->badgesForRole($role);

        $conversation->users()->updateExistingPivot($target->id, [
            'role' => $role,
            'badges' => json_encode($badges),
        ]);

        $this->audit($conversation, $actor, 'role.changed', $target->id, [
            'from' => $oldRole,
            'to' => $role,
        ], 'Role changed');

        $this->postSystemEvent($conversation, $actor, 'role_changed', [
            'target_id' => $target->id,
            'role' => $role,
        ]);

        $this->broadcastToMembers($conversation, 0, 'member.role_changed', [
            'conversation_id' => $conversation->id,
            'user_id' => $target->id,
            'role' => $role,
        ]);
        $this->broadcastConversationUpdated($conversation->fresh());
    }

    public function transferOwnership(User $actor, Conversation $conversation, int $newOwnerId): Conversation
    {
        $this->assertCommunity($conversation);
        $this->permissions->assertCan($actor, $conversation, 'transfer_ownership');

        if ((int) $conversation->owner_id !== (int) $actor->id) {
            throw new \RuntimeException('Only the owner can transfer ownership');
        }

        $newOwner = User::findOrFail($newOwnerId);
        if (! $conversation->hasParticipant($newOwner)) {
            throw new \InvalidArgumentException('New owner must be a member');
        }

        DB::transaction(function () use ($conversation, $actor, $newOwner) {
            $conversation->users()->updateExistingPivot($actor->id, [
                'role' => 'admin',
                'badges' => json_encode(['admin']),
            ]);
            $conversation->users()->updateExistingPivot($newOwner->id, [
                'role' => 'owner',
                'badges' => json_encode(['owner']),
            ]);
            $conversation->update(['owner_id' => $newOwner->id]);

            $this->audit($conversation, $actor, 'ownership.transferred', $newOwner->id);
            $this->postSystemEvent($conversation, $actor, 'ownership_transferred', [
                'target_id' => $newOwner->id,
            ]);
        });

        $this->broadcastToMembers($conversation, 0, 'member.role_changed', [
            'conversation_id' => $conversation->id,
            'user_id' => $newOwner->id,
            'role' => 'owner',
            'previous_owner_id' => $actor->id,
        ]);

        return $this->loadCommunity($conversation->fresh(), $actor);
    }

    public function leave(User $user, Conversation $conversation): void
    {
        $this->assertCommunity($conversation);

        // Allow leaving whenever the user still has a membership row visible in
        // their sidebar (deleted_at null), even if is_active was cleared earlier.
        $pivot = DB::table('conversation_user')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->first();

        if (! $pivot) {
            throw new \RuntimeException('Forbidden');
        }

        $isOwner = ((int) $conversation->owner_id === (int) $user->id)
            || (($pivot->role ?? null) === 'owner');

        if ($isOwner) {
            $otherMembers = DB::table('conversation_user')
                ->where('conversation_id', $conversation->id)
                ->where('user_id', '!=', $user->id)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->where('is_active', true)->orWhereNull('is_active');
                })
                ->count();

            if ($otherMembers > 0) {
                throw new \InvalidArgumentException(
                    'Owner must transfer ownership before leaving'
                );
            }
        }

        $conversation->users()->updateExistingPivot($user->id, [
            'deleted_at' => now(),
            'is_active' => false,
        ]);
        $this->refreshMemberCount($conversation);

        // Only emit join/leave system events when the member was actually active.
        if ($pivot->is_active !== false && $pivot->is_active !== 0 && $pivot->is_active !== '0') {
            $this->postSystemEvent($conversation, $user, 'user_left', [
                'target_id' => $user->id,
                'target_name' => $this->userDisplayName($user),
            ]);
            $this->broadcastToMembers($conversation, $user->id, 'member.left', [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ]);
        }

        $this->messenger->emitEvent($user->id, $conversation->id, 'conversation.deleted', [
            'conversation_id' => $conversation->id,
        ]);
    }

    public function kick(User $actor, Conversation $conversation, int $userId, ?string $reason = null): void
    {
        $this->assertCommunity($conversation);
        $this->permissions->assertCan($actor, $conversation, 'kick_members');

        $target = User::findOrFail($userId);
        if (! $this->permissions->canModerate($actor, $conversation, $target)) {
            throw new \RuntimeException('Forbidden');
        }

        $conversation->users()->updateExistingPivot($userId, [
            'deleted_at' => now(),
            'is_active' => false,
        ]);
        $this->refreshMemberCount($conversation);

        $this->audit($conversation, $actor, 'member.kicked', $userId, null, $reason);
        $this->postSystemEvent($conversation, $actor, 'user_removed', [
            'target_id' => $userId,
            'target_name' => $this->userDisplayName($target),
        ]);
        $this->broadcastToMembers($conversation, $userId, 'member.left', [
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'kicked' => true,
        ]);
        $this->messenger->emitEvent($userId, $conversation->id, 'conversation.deleted', [
            'conversation_id' => $conversation->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Join / invites / requests
    // -------------------------------------------------------------------------

    public function joinPublic(User $user, Conversation $conversation): Conversation
    {
        $this->assertCommunity($conversation);

        if (! $conversation->is_public) {
            throw new \RuntimeException('This group is private');
        }

        if ($this->isActivelyBanned($conversation, $user)) {
            throw new \RuntimeException('You are banned from this group');
        }

        if ($conversation->hasParticipant($user)) {
            return $this->loadCommunity($conversation, $user);
        }

        if ($conversation->join_approval_required) {
            return $this->requestJoin($user, $conversation);
        }

        $this->attachMember($conversation, $user, $conversation->isChannel() ? 'subscriber' : 'member');
        $this->postSystemEvent($conversation, $user, 'user_joined', [
            'target_id' => $user->id,
            'target_name' => $this->userDisplayName($user),
        ]);
        $this->broadcastToMembers($conversation, $user->id, 'member.joined', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
        $this->broadcastConversationUpdated($conversation->fresh());

        return $this->loadCommunity($conversation->fresh(), $user);
    }

    public function joinByUsername(User $user, string $username): Conversation
    {
        $username = $this->normalizeUsername($username);
        $conversation = Conversation::where('username', $username)
            ->whereIn('type', [Conversation::TYPE_GROUP, Conversation::TYPE_CHANNEL])
            ->where('is_archived', false)
            ->firstOrFail();

        if (! $conversation->is_public && ! $conversation->hasParticipant($user)) {
            throw new \RuntimeException('This group is private');
        }

        if ($conversation->hasParticipant($user)) {
            return $this->loadCommunity($conversation, $user);
        }

        return $this->joinPublic($user, $conversation);
    }

    /**
     * Telegram-style join preview for invite codes / public usernames.
     *
     * @return array{conversation: Conversation, is_member: bool, invite_code: ?string, can_preview: bool}
     */
    public function previewJoin(User $user, ?string $code = null, ?string $username = null): array
    {
        $inviteCode = null;
        $conversation = null;

        if ($code) {
            $invite = ConversationInvite::where('code', Str::lower(trim($code)))->firstOrFail();
            if (! $invite->isValid() && ! $invite->conversation?->hasParticipant($user)) {
                throw new \RuntimeException('Invite link is invalid or expired');
            }
            $conversation = $invite->conversation;
            $inviteCode = $invite->code;
        } elseif ($username) {
            $username = $this->normalizeUsername($username);
            $conversation = Conversation::where('username', $username)
                ->whereIn('type', [Conversation::TYPE_GROUP, Conversation::TYPE_CHANNEL])
                ->where('is_archived', false)
                ->firstOrFail();
        } else {
            throw new \InvalidArgumentException('code or username is required');
        }

        $this->assertCommunity($conversation);

        if ($this->isActivelyBanned($conversation, $user)) {
            throw new \RuntimeException('You are banned from this group');
        }

        $isMember = $conversation->hasParticipant($user);
        $canPreview = $isMember || $conversation->is_public;

        if (! $isMember && ! $conversation->is_public && ! $inviteCode) {
            throw new \RuntimeException('This group is private');
        }

        $conversation->load([
            'owner:id,first_name,last_name,username,profile_pic',
        ]);
        $this->refreshMemberCount($conversation);
        $conversation->refresh();
        $conversation->setResponseAttribute('is_preview', ! $isMember);
        $conversation->setResponseAttribute('my_role', $isMember ? $conversation->memberRole($user) : null);

        return [
            'conversation' => $conversation,
            'is_member' => $isMember,
            'invite_code' => $inviteCode,
            'can_preview' => $canPreview,
        ];
    }

    public function requestJoin(User $user, Conversation $conversation, ?string $message = null): Conversation
    {
        $this->assertCommunity($conversation);

        if ($conversation->hasParticipant($user)) {
            return $this->loadCommunity($conversation, $user);
        }

        if ($this->isActivelyBanned($conversation, $user)) {
            throw new \RuntimeException('You are banned from this group');
        }

        $existing = ConversationJoinRequest::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->pending()
            ->first();

        if (! $existing) {
            ConversationJoinRequest::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'status' => ConversationJoinRequest::STATUS_PENDING,
                'message' => $message,
            ]);
        }

        $this->notifyAdmins($conversation, 'join_request.created', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        // Return conversation without membership for pending state
        $conversation->load(['owner:id,first_name,last_name,username,profile_pic']);
        $conversation->join_request_pending = true;

        return $conversation;
    }

    public function listJoinRequests(User $user, Conversation $conversation, string $status = 'pending'): LengthAwarePaginator
    {
        $this->permissions->assertCan($user, $conversation, 'manage_join_requests');

        return ConversationJoinRequest::where('conversation_id', $conversation->id)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['user:id,first_name,last_name,username,profile_pic', 'reviewer:id,first_name,last_name,username'])
            ->orderByDesc('id')
            ->paginate(40);
    }

    public function approveJoinRequest(User $actor, Conversation $conversation, int $requestId): void
    {
        $this->permissions->assertCan($actor, $conversation, 'manage_join_requests');

        $request = ConversationJoinRequest::where('conversation_id', $conversation->id)
            ->whereKey($requestId)
            ->pending()
            ->firstOrFail();

        $target = User::findOrFail($request->user_id);

        DB::transaction(function () use ($request, $actor, $conversation, $target) {
            $request->update([
                'status' => ConversationJoinRequest::STATUS_APPROVED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
            ]);
            $this->attachMember($conversation, $target, $conversation->isChannel() ? 'subscriber' : 'member');
            $this->audit($conversation, $actor, 'join_request.approved', $target->id);
            $this->postSystemEvent($conversation, $target, 'user_joined', [
                'target_id' => $target->id,
                'target_name' => $this->userDisplayName($target),
            ]);
        });

        $this->broadcastToMembers($conversation, 0, 'member.joined', [
            'conversation_id' => $conversation->id,
            'user_id' => $target->id,
        ]);
    }

    public function rejectJoinRequest(User $actor, Conversation $conversation, int $requestId, ?string $note = null): void
    {
        $this->permissions->assertCan($actor, $conversation, 'manage_join_requests');

        $request = ConversationJoinRequest::where('conversation_id', $conversation->id)
            ->whereKey($requestId)
            ->pending()
            ->firstOrFail();

        $request->update([
            'status' => ConversationJoinRequest::STATUS_REJECTED,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);

        $this->audit($conversation, $actor, 'join_request.rejected', $request->user_id, null, $note);
        $this->messenger->emitEvent($request->user_id, $conversation->id, 'join_request.rejected', [
            'conversation_id' => $conversation->id,
        ]);
    }

    public function createInvite(User $user, Conversation $conversation, array $data): ConversationInvite
    {
        $this->permissions->assertCan($user, $conversation, 'create_invite_links');
        $this->assertWhoCanStaffGate($user, $conversation, $conversation->who_can_invite);

        $invite = ConversationInvite::create([
            'conversation_id' => $conversation->id,
            'created_by' => $user->id,
            'code' => Str::lower(Str::random(config('messenger_groups.invite_code_length', 16))),
            'max_uses' => $data['max_uses'] ?? null,
            'expires_at' => isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
            'is_temporary' => (bool) ($data['is_temporary'] ?? false),
        ]);

        $this->audit($conversation, $user, 'invite.created', null, ['invite_id' => $invite->id]);
        $this->postSystemEvent($conversation, $user, 'invite_link_created', ['invite_id' => $invite->id]);

        return $invite->load('creator:id,first_name,last_name,username');
    }

    public function listInvites(User $user, Conversation $conversation): Collection
    {
        $this->permissions->assertCan($user, $conversation, 'manage_invite_links');

        return ConversationInvite::where('conversation_id', $conversation->id)
            ->with('creator:id,first_name,last_name,username')
            ->orderByDesc('id')
            ->get();
    }

    public function revokeInvite(User $user, Conversation $conversation, int $inviteId): void
    {
        $this->permissions->assertCan($user, $conversation, 'manage_invite_links');

        $invite = ConversationInvite::where('conversation_id', $conversation->id)
            ->whereKey($inviteId)
            ->firstOrFail();

        $invite->update(['is_revoked' => true, 'revoked_at' => now()]);
        $this->audit($conversation, $user, 'invite.revoked', null, ['invite_id' => $invite->id]);
        $this->postSystemEvent($conversation, $user, 'invite_link_revoked', ['invite_id' => $invite->id]);
    }

    public function joinByInvite(User $user, string $code): Conversation
    {
        $invite = ConversationInvite::where('code', $code)->firstOrFail();
        $conversation = $invite->conversation;

        $this->assertCommunity($conversation);

        if (! $invite->isValid()) {
            throw new \InvalidArgumentException('Invite link is invalid or expired');
        }

        if ($this->isActivelyBanned($conversation, $user)) {
            throw new \RuntimeException('You are banned from this group');
        }

        if ($conversation->hasParticipant($user)) {
            return $this->loadCommunity($conversation, $user);
        }

        DB::transaction(function () use ($invite, $conversation, $user) {
            $invite->increment('uses_count');
            $this->attachMember(
                $conversation,
                $user,
                $conversation->isChannel() ? 'subscriber' : 'member',
                $invite->is_temporary
            );
            $this->postSystemEvent($conversation, $user, 'user_joined', [
                'target_id' => $user->id,
                'target_name' => $this->userDisplayName($user),
            ]);
        });

        $this->broadcastToMembers($conversation, $user->id, 'member.joined', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
        $this->broadcastConversationUpdated($conversation->fresh());

        return $this->loadCommunity($conversation->fresh(), $user);
    }

    // -------------------------------------------------------------------------
    // Ban / mute
    // -------------------------------------------------------------------------

    public function ban(User $actor, Conversation $conversation, int $userId, ?string $reason = null, ?int $expiresInSeconds = null): ConversationBan
    {
        $this->permissions->assertCan($actor, $conversation, 'ban_members');
        $target = User::findOrFail($userId);

        if (! $this->permissions->canModerate($actor, $conversation, $target)) {
            throw new \RuntimeException('Forbidden');
        }

        $ban = DB::transaction(function () use ($conversation, $actor, $target, $reason, $expiresInSeconds) {
            ConversationBan::where('conversation_id', $conversation->id)
                ->where('user_id', $target->id)
                ->whereNull('unbanned_at')
                ->update(['unbanned_at' => now(), 'unbanned_by' => $actor->id]);

            $ban = ConversationBan::create([
                'conversation_id' => $conversation->id,
                'user_id' => $target->id,
                'banned_by' => $actor->id,
                'reason' => $reason,
                'expires_at' => $expiresInSeconds ? now()->addSeconds($expiresInSeconds) : null,
            ]);

            $wasActive = DB::table('conversation_user')
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $target->id)
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->exists();

            $conversation->users()->updateExistingPivot($target->id, [
                'is_banned' => true,
                'is_active' => false,
                'deleted_at' => now(),
            ]);

            if ($wasActive) {
                // refreshed below
            }

            $this->refreshMemberCount($conversation);

            $this->audit($conversation, $actor, 'member.banned', $target->id, [
                'expires_at' => $ban->expires_at?->toIso8601String(),
            ], $reason);
            $this->postSystemEvent($conversation, $actor, 'user_banned', [
                'target_id' => $target->id,
                'target_name' => $this->userDisplayName($target),
            ]);

            return $ban;
        });

        $this->broadcastToMembers($conversation, $userId, 'member.banned', [
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
        ]);
        $this->messenger->emitEvent($userId, $conversation->id, 'conversation.deleted', [
            'conversation_id' => $conversation->id,
        ]);

        return $ban->load(['user:id,first_name,last_name,username', 'bannedBy:id,first_name,last_name,username']);
    }

    public function unban(User $actor, Conversation $conversation, int $userId): void
    {
        $this->permissions->assertCan($actor, $conversation, 'unban_members');

        $ban = ConversationBan::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->active()
            ->latest('id')
            ->first();

        if (! $ban) {
            throw new \InvalidArgumentException('User is not banned');
        }

        $ban->update(['unbanned_at' => now(), 'unbanned_by' => $actor->id]);

        $conversation->users()->updateExistingPivot($userId, [
            'is_banned' => false,
        ]);

        $this->audit($conversation, $actor, 'member.unbanned', $userId);
        $this->postSystemEvent($conversation, $actor, 'user_unbanned', ['target_id' => $userId]);
        $this->broadcastToMembers($conversation, 0, 'member.unbanned', [
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
        ]);
    }

    public function listBans(User $user, Conversation $conversation): LengthAwarePaginator
    {
        $this->permissions->assertCan($user, $conversation, 'ban_members');

        return ConversationBan::where('conversation_id', $conversation->id)
            ->active()
            ->with([
                'user:id,first_name,last_name,username,profile_pic',
                'bannedBy:id,first_name,last_name,username',
            ])
            ->orderByDesc('id')
            ->paginate(40);
    }

    public function muteMember(User $actor, Conversation $conversation, int $userId, ?int $seconds = null): void
    {
        $this->permissions->assertCan($actor, $conversation, 'mute_members');
        $target = User::findOrFail($userId);

        if (! $this->permissions->canModerate($actor, $conversation, $target)) {
            throw new \RuntimeException('Forbidden');
        }

        $until = $seconds === null ? Carbon::create(9999, 12, 31) : now()->addSeconds($seconds);

        $conversation->users()->updateExistingPivot($userId, [
            'muted_until' => $until,
            'muted_at' => now(),
        ]);

        $this->audit($conversation, $actor, 'member.muted', $userId, [
            'muted_until' => $until->toIso8601String(),
        ]);
        $this->broadcastToMembers($conversation, 0, 'member.muted', [
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'muted_until' => $until->toIso8601String(),
        ]);
        $this->messenger->emitEvent($userId, $conversation->id, 'member.muted', [
            'conversation_id' => $conversation->id,
            'muted_until' => $until->toIso8601String(),
        ]);
    }

    public function unmuteMember(User $actor, Conversation $conversation, int $userId): void
    {
        $this->permissions->assertCan($actor, $conversation, 'mute_members');

        $conversation->users()->updateExistingPivot($userId, [
            'muted_until' => null,
        ]);

        $this->audit($conversation, $actor, 'member.unmuted', $userId);
        $this->broadcastToMembers($conversation, 0, 'member.unmuted', [
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
        ]);
    }

    // -------------------------------------------------------------------------
    // Search
    // -------------------------------------------------------------------------

    public function searchCommunities(User $user, string $query, ?string $type = null, int $limit = 20): Collection
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return new Collection;
        }

        return Conversation::query()
            ->whereIn('type', $type
                ? [$type]
                : [Conversation::TYPE_GROUP, Conversation::TYPE_CHANNEL])
            ->where('is_public', true)
            ->where('is_archived', false)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%");
            })
            ->orderByDesc('member_count')
            ->limit($limit)
            ->get([
                'id', 'type', 'title', 'description', 'avatar', 'username',
                'member_count', 'is_verified', 'is_public',
            ]);
    }

    public function searchMessages(
        User $user,
        Conversation $conversation,
        array $filters
    ): LengthAwarePaginator {
        $this->messengerAssertParticipant($user, $conversation);

        $q = Message::query()
            ->visibleTo($user)
            ->where('conversation_id', $conversation->id)
            ->where('type', '!=', 'system');

        if (! empty($filters['q'])) {
            $term = trim($filters['q']);
            $q->where('body', 'like', "%{$term}%");
        }

        if (! empty($filters['user_id'])) {
            $q->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['from'])) {
            $q->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $q->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        if (! empty($filters['pinned'])) {
            $q->whereExists(function ($sub) use ($user) {
                $sub->selectRaw('1')
                    ->from('message_pins')
                    ->whereColumn('message_pins.message_id', 'messages.id')
                    ->where('message_pins.user_id', $user->id);
            });
        }

        if (! empty($filters['links'])) {
            $q->where(function ($w) {
                $w->where('body', 'like', '%http://%')
                    ->orWhere('body', 'like', '%https://%')
                    ->orWhere('body', 'like', '%www.%');
            });
        }

        return $q->with([
            'user:id,first_name,last_name,username,profile_pic',
        ])
            ->orderByDesc('id')
            ->paginate(40);
    }

    // -------------------------------------------------------------------------
    // Reactions / views
    // -------------------------------------------------------------------------

    public function toggleReaction(User $user, Message $message, string $emoji): array
    {
        $message = Message::query()->visibleTo($user)->findOrFail($message->id);
        $conversation = $message->conversation;

        if ($conversation->isCommunity() && ! $conversation->reactions_enabled) {
            throw new \RuntimeException('Reactions are disabled');
        }

        $emoji = trim($emoji);
        if ($emoji === '' || mb_strlen($emoji) > 32) {
            throw new \InvalidArgumentException('Invalid emoji');
        }

        $existing = MessageReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();
            $removed = true;
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $emoji,
            ]);
            $removed = false;
        }

        $summary = $this->reactionSummary($message->id);

        $this->broadcastToMembers($conversation, 0, 'message.reaction', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $emoji,
            'removed' => $removed,
            'reactions' => $summary,
        ]);

        return ['removed' => $removed, 'reactions' => $summary];
    }

    public function reactionSummary(int $messageId): array
    {
        return MessageReaction::where('message_id', $messageId)
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['emoji' => $r->emoji, 'count' => (int) $r->count])
            ->all();
    }

    public function recordView(User $user, Message $message): int
    {
        $message = Message::query()->visibleTo($user)->findOrFail($message->id);
        $conversation = $message->conversation;

        if (! $conversation->isChannel()) {
            return (int) $message->view_count;
        }

        $created = MessageView::firstOrCreate(
            ['message_id' => $message->id, 'user_id' => $user->id],
            ['viewed_at' => now()]
        );

        if ($created->wasRecentlyCreated) {
            $message->increment('view_count');
        }

        return (int) $message->fresh()->view_count;
    }

    /**
     * Record views for many channel messages in one round-trip.
     * Returns a map of message_id => view_count for visible messages.
     *
     * @param  list<int|string>  $messageIds
     * @return array<int, int>
     */
    public function recordViews(User $user, array $messageIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $messageIds),
            fn (int $id) => $id > 0
        )));

        if ($ids === []) {
            return [];
        }

        $messages = Message::query()
            ->visibleTo($user)
            ->whereIn('messages.id', $ids)
            ->with('conversation:id,type')
            ->get(['messages.id', 'messages.conversation_id', 'messages.view_count', 'messages.type']);

        if ($messages->isEmpty()) {
            return [];
        }

        $result = [];

        foreach ($messages as $message) {
            $mid = (int) $message->id;

            if (! $message->conversation?->isChannel() || $message->type === 'system') {
                $result[$mid] = (int) $message->view_count;

                continue;
            }

            $created = MessageView::firstOrCreate(
                ['message_id' => $mid, 'user_id' => $user->id],
                ['viewed_at' => now()]
            );

            if ($created->wasRecentlyCreated) {
                $message->increment('view_count');
                $result[$mid] = (int) $message->fresh()->view_count;
            } else {
                $result[$mid] = (int) $message->view_count;
            }
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Audit logs
    // -------------------------------------------------------------------------

    public function listAuditLogs(User $user, Conversation $conversation): LengthAwarePaginator
    {
        $this->permissions->assertCan($user, $conversation, 'view_audit_logs');

        return ConversationAuditLog::where('conversation_id', $conversation->id)
            ->with([
                'actor:id,first_name,last_name,username,profile_pic',
                'targetUser:id,first_name,last_name,username,profile_pic',
            ])
            ->orderByDesc('id')
            ->paginate(50);
    }

    public function setRolePermission(User $user, Conversation $conversation, string $role, string $permission, bool $allowed): array
    {
        $this->permissions->assertCan($user, $conversation, 'manage_roles');
        $this->permissions->setPermission($conversation, $role, $permission, $allowed);
        $this->audit($conversation, $user, 'permission.updated', null, compact('role', 'permission', 'allowed'));

        return $this->permissions->matrixFor($conversation);
    }

    // -------------------------------------------------------------------------
    // Send guards (called from MessengerService)
    // -------------------------------------------------------------------------

    public function assertCanSend(User $user, Conversation $conversation, string $body = ''): void
    {
        if (! $conversation->isCommunity()) {
            return;
        }

        $pivot = $conversation->memberPivot($user);
        if (! $pivot || $pivot->is_banned || ! $pivot->is_active) {
            throw new \RuntimeException('Forbidden');
        }

        if ($pivot->muted_until && Carbon::parse($pivot->muted_until)->isFuture()) {
            throw new \RuntimeException('You are muted in this group');
        }

        if ($conversation->isChannel()) {
            $this->permissions->assertCan($user, $conversation, 'publish_posts');
        } else {
            // Chat lock: only staff can send while locked.
            if ($conversation->isChatLockedNow()) {
                $rank = $this->permissions->roleRank((string) $pivot->role);
                if ($rank < $this->permissions->roleRank('moderator')) {
                    throw new \RuntimeException('Chat is locked');
                }
            }

            if ($conversation->who_can_send === 'admins') {
                $rank = $this->permissions->roleRank($pivot->role);
                if ($rank < $this->permissions->roleRank('moderator')) {
                    throw new \RuntimeException('Only admins can send messages');
                }
            }
            $this->permissions->assertCan($user, $conversation, 'send_messages');
        }

        if ($conversation->slow_mode_seconds > 0) {
            $this->assertSlowMode($user, $conversation);
        }

        $this->assertAntiSpam($user, $conversation, $body);
        $this->assertMentionsAllowed($user, $conversation, $body);
    }

    public function checkUsernameAvailable(?string $username, ?int $exceptId = null): array
    {
        $username = $this->normalizeUsername($username);
        if (! $username) {
            return ['available' => false, 'username' => null, 'reason' => 'empty'];
        }

        try {
            $this->assertUsernameAvailable($username, $exceptId);
        } catch (\InvalidArgumentException $e) {
            return ['available' => false, 'username' => $username, 'reason' => 'taken'];
        }

        return ['available' => true, 'username' => $username, 'reason' => null];
    }

    public function refreshMemberCount(Conversation $conversation): int
    {
        $count = DB::table('conversation_user')
            ->where('conversation_id', $conversation->id)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->where('is_banned', false)
            ->count();

        // Update via query so in-memory response-only attributes (e.g. unread_count)
        // are never accidentally written as columns.
        Conversation::whereKey($conversation->id)->update(['member_count' => $count]);
        $conversation->setResponseAttribute('member_count', $count);

        return $count;
    }

    public function assertCanEdit(User $user, Conversation $conversation, Message $message): void
    {
        if (! $conversation->isCommunity()) {
            return;
        }

        if ($message->isOwnedBy($user)) {
            $this->permissions->assertCan($user, $conversation, 'edit_own_messages');
        } else {
            throw new \RuntimeException('You can only edit your own messages');
        }
    }

    public function assertCanDelete(User $user, Conversation $conversation, Message $message, string $scope): void
    {
        if (! $conversation->isCommunity() || $scope !== 'everyone') {
            return;
        }

        if ($message->isOwnedBy($user)) {
            $this->permissions->assertCan($user, $conversation, 'delete_own_messages');
        } else {
            $this->permissions->assertCan($user, $conversation, 'delete_others_messages');
        }
    }

    public function assertCanPin(User $user, Conversation $conversation): void
    {
        if (! $conversation->isCommunity()) {
            return;
        }

        $this->permissions->assertCan($user, $conversation, 'pin_messages');
        $this->assertWhoCanStaffGate($user, $conversation, $conversation->who_can_pin);
    }

    /**
     * Conversation-level "admins only" gate (in addition to role permissions).
     * who_can_* = all → no extra gate; otherwise require moderator+.
     */
    protected function assertWhoCanStaffGate(User $user, Conversation $conversation, ?string $whoCan): void
    {
        if (($whoCan ?? 'admins') === 'all') {
            return;
        }

        $pivot = $conversation->memberPivot($user);
        $rank = $this->permissions->roleRank((string) ($pivot?->role ?? ''));
        if ($rank < $this->permissions->roleRank('moderator')) {
            throw new \RuntimeException('Forbidden');
        }
    }

    protected function normalizeLockHm(mixed $value): ?string
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

    public function onMessageSent(User $user, Conversation $conversation): void
    {
        if (! $conversation->isCommunity()) {
            return;
        }

        $conversation->increment('message_count');
        DB::table('conversation_user')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->increment('message_count');

        Cache::put($this->slowModeKey($user->id, $conversation->id), now()->timestamp, $conversation->slow_mode_seconds + 5);
        $this->trackSpamWindow($user, $conversation);
    }

    public function parseMentions(string $body): array
    {
        $mentions = [];
        if (preg_match_all('/@([a-zA-Z0-9_]{2,64}|everyone|admins)/u', $body, $matches)) {
            foreach ($matches[1] as $m) {
                $mentions[] = strtolower($m);
            }
        }

        return array_values(array_unique($mentions));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function attachMember(Conversation $conversation, User $user, string $role, bool $temporary = false): void
    {
        $now = now();
        $exists = DB::table('conversation_user')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->exists();

        $payload = [
            'role' => $role,
            'joined_at' => $now,
            'is_active' => true,
            'is_banned' => false,
            'deleted_at' => null,
            'cleared_at' => $conversation->history_visible ? null : $now,
            'badges' => json_encode($this->badgesForRole($role)),
            'updated_at' => $now,
        ];

        if ($exists) {
            $conversation->users()->updateExistingPivot($user->id, $payload);
        } else {
            $payload['created_at'] = $now;
            $conversation->users()->attach($user->id, $payload);
        }

        $this->refreshMemberCount($conversation);
    }

    protected function hasMembershipRow(Conversation $conversation, User $user): bool
    {
        return DB::table('conversation_user')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    protected function isActivelyBanned(Conversation $conversation, User $user): bool
    {
        return ConversationBan::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->active()
            ->exists();
    }

    protected function assertCommunity(Conversation $conversation): void
    {
        if (! $conversation->isCommunity()) {
            throw new \InvalidArgumentException('Not a group or channel');
        }
    }

    protected function messengerAssertParticipant(User $user, Conversation $conversation): void
    {
        if (! $conversation->hasParticipant($user)) {
            throw new \RuntimeException('Forbidden');
        }
    }

    protected function normalizeUsername(?string $username): ?string
    {
        if ($username === null || trim($username) === '') {
            return null;
        }

        $username = strtolower(ltrim(trim($username), '@'));
        if (! preg_match('/^[a-z][a-z0-9_]{2,31}$/', $username)) {
            throw new \InvalidArgumentException('Username must be 3–32 chars, start with a letter, and use a-z, 0-9, _');
        }

        return $username;
    }

    protected function assertUsernameAvailable(string $username, ?int $exceptId = null): void
    {
        $q = Conversation::where('username', $username);
        if ($exceptId) {
            $q->where('id', '!=', $exceptId);
        }
        if ($q->exists()) {
            throw new \InvalidArgumentException('Username is already taken');
        }
    }

    protected function badgesForRole(string $role): array
    {
        return match ($role) {
            'owner' => ['owner'],
            'admin' => ['admin'],
            'moderator' => ['moderator'],
            default => [],
        };
    }

    protected function decodeBadges($badges): array
    {
        if (is_array($badges)) {
            return $badges;
        }
        if (is_string($badges)) {
            $decoded = json_decode($badges, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function loadCommunity(Conversation $conversation, User $user): Conversation
    {
        $this->refreshMemberCount($conversation);
        $conversation->refresh();

        $conversation->load([
            'users:id,first_name,last_name,username,profile_pic,last_seen',
            'users.messengerSettings',
            'owner:id,first_name,last_name,username,profile_pic',
        ]);
        $conversation->setRelation(
            'lastMessage',
            $this->messenger->lastVisibleMessageFor($user, $conversation)
        );
        $conversation->setResponseAttribute('my_role', $conversation->memberRole($user));
        $conversation->setResponseAttribute(
            'permissions',
            $this->permissions->matrixFor($conversation)[$conversation->my_role] ?? []
        );

        return $conversation;
    }

    protected function serializeConversationBrief(Conversation $conversation, User $user): array
    {
        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'title' => $conversation->title,
            'description' => $conversation->description,
            'avatar' => $conversation->avatar,
            'cover' => $conversation->cover,
            'username' => $conversation->username,
            'member_count' => (int) $conversation->member_count,
            'message_count' => (int) $conversation->message_count,
            'is_public' => (bool) $conversation->is_public,
            'is_verified' => (bool) $conversation->is_verified,
            'is_archived' => (bool) $conversation->is_archived,
            'owner_id' => $conversation->owner_id,
            'slow_mode_seconds' => (int) $conversation->slow_mode_seconds,
            'history_visible' => (bool) $conversation->history_visible,
            'join_approval_required' => (bool) $conversation->join_approval_required,
            'reactions_enabled' => (bool) $conversation->reactions_enabled,
            'signatures_enabled' => (bool) $conversation->signatures_enabled,
            'who_can_send' => $conversation->who_can_send,
            'who_can_invite' => $conversation->who_can_invite,
            'who_can_pin' => $conversation->who_can_pin,
            'who_can_edit_info' => $conversation->who_can_edit_info,
            'messages_locked' => (bool) $conversation->messages_locked,
            'messages_locked_until' => $conversation->messages_locked_until?->toIso8601String(),
            'lock_schedule' => $conversation->lock_schedule,
            'chat_locked_now' => $conversation->isChatLockedNow(),
            'my_role' => $conversation->memberRole($user),
            'updated_at' => $conversation->updated_at?->toIso8601String(),
        ];
    }

    protected function broadcastConversationUpdated(Conversation $conversation): void
    {
        $ids = $conversation->users()
            ->whereNull('conversation_user.deleted_at')
            ->where('conversation_user.is_active', true)
            ->pluck('users.id');

        foreach ($ids as $uid) {
            $viewer = User::find($uid);
            if (! $viewer) {
                continue;
            }
            $this->messenger->emitEvent((int) $uid, $conversation->id, 'conversation.updated', [
                'conversation_id' => $conversation->id,
                'conversation' => $this->serializeConversationBrief($conversation, $viewer),
            ], true);
        }
    }

    public function audit(
        Conversation $conversation,
        ?User $actor,
        string $action,
        ?int $targetUserId = null,
        ?array $meta = null,
        ?string $reason = null,
        ?int $targetId = null,
        ?string $targetType = null
    ): void {
        ConversationAuditLog::create([
            'conversation_id' => $conversation->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'target_user_id' => $targetUserId,
            'target_id' => $targetId,
            'target_type' => $targetType,
            'reason' => $reason,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }

    protected function postSystemEvent(
        Conversation $conversation,
        User $actor,
        string $event,
        array $meta = [],
        ?int $onlyForUserId = null
    ): void {
        $actorName = trim("{$actor->first_name} {$actor->last_name}") ?: ($actor->username ?: 'User');
        $payload = array_merge([
            'event' => $event,
            'actor_id' => $actor->id,
            'actor_name' => $actorName,
            'actor_username' => $actor->username,
        ], $meta);

        if (! empty($meta['target_id']) && empty($meta['target_name'])) {
            $target = User::select('id', 'first_name', 'last_name', 'username')->find($meta['target_id']);
            if ($target) {
                $payload['target_name'] = trim("{$target->first_name} {$target->last_name}") ?: ($target->username ?: 'User');
                $payload['target_username'] = $target->username;
            }
        }

        // Channel join/leave: only visible to that member (Telegram-style).
        if ($onlyForUserId === null
            && $conversation->isChannel()
            && in_array($event, ['user_joined', 'user_left'], true)
        ) {
            $onlyForUserId = (int) ($meta['target_id'] ?? $actor->id);
        }

        $body = json_encode($payload);

        try {
            $message = $this->messenger->sendSystemMessage($conversation, $actor, $body, $event);

            if ($onlyForUserId) {
                $others = $conversation->users()
                    ->where('users.id', '!=', $onlyForUserId)
                    ->pluck('users.id');

                $rows = [];
                $now = now();
                foreach ($others as $uid) {
                    $rows[] = [
                        'message_id' => $message->id,
                        'user_id' => $uid,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($rows) {
                    DB::table('message_deletions')->insertOrIgnore($rows);
                }

                // Retract the fan-out for others by emitting delete to them.
                foreach ($others as $uid) {
                    $this->messenger->emitEvent((int) $uid, $conversation->id, 'message.deleted', [
                        'message_id' => $message->id,
                        'conversation_id' => $conversation->id,
                        'scope' => 'me',
                    ], false);
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function generatePrivateUsername(string $type): string
    {
        $prefix = $type === Conversation::TYPE_CHANNEL ? 'c' : 'g';
        for ($i = 0; $i < 8; $i++) {
            $candidate = $prefix.Str::lower(Str::random(10));
            if (! Conversation::where('username', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $prefix.Str::lower(Str::random(16));
    }

    protected function userDisplayName(User $user): string
    {
        return trim("{$user->first_name} {$user->last_name}") ?: ($user->username ?: 'User');
    }

    protected function broadcastToMembers(Conversation $conversation, int $excludeUserId, string $type, array $payload): void
    {
        $ids = $conversation->users()
            ->whereNull('conversation_user.deleted_at')
            ->where('conversation_user.is_active', true)
            ->when($excludeUserId > 0, fn ($q) => $q->where('users.id', '!=', $excludeUserId))
            ->pluck('users.id');

        foreach ($ids as $uid) {
            $this->messenger->emitEvent((int) $uid, $conversation->id, $type, $payload);
        }
    }

    protected function notifyAdmins(Conversation $conversation, string $type, array $payload): void
    {
        $ids = $conversation->users()
            ->whereNull('conversation_user.deleted_at')
            ->whereIn('conversation_user.role', ['owner', 'admin', 'moderator'])
            ->pluck('users.id');

        foreach ($ids as $uid) {
            $this->messenger->emitEvent((int) $uid, $conversation->id, $type, $payload);
        }
    }

    protected function assertSlowMode(User $user, Conversation $conversation): void
    {
        $rank = $this->permissions->roleRank((string) $conversation->memberRole($user));
        if ($rank >= $this->permissions->roleRank('admin')) {
            return;
        }

        $last = Cache::get($this->slowModeKey($user->id, $conversation->id));
        if ($last) {
            $elapsed = now()->timestamp - (int) $last;
            $wait = $conversation->slow_mode_seconds - $elapsed;
            if ($wait > 0) {
                throw new \RuntimeException("Slow mode: wait {$wait} seconds");
            }
        }
    }

    protected function slowModeKey(int $userId, int $conversationId): string
    {
        return "messenger:slow:{$conversationId}:{$userId}";
    }

    protected function assertAntiSpam(User $user, Conversation $conversation, string $body): void
    {
        $cfg = config('messenger_groups.anti_spam');
        $key = "messenger:spam:{$conversation->id}:{$user->id}";
        $window = Cache::get($key, ['count' => 0, 'bodies' => [], 'started' => now()->timestamp]);

        if (now()->timestamp - ($window['started'] ?? 0) > $cfg['window_seconds']) {
            $window = ['count' => 0, 'bodies' => [], 'started' => now()->timestamp];
        }

        $window['count']++;
        $hash = md5(mb_strtolower(trim($body)));
        $window['bodies'][] = $hash;
        $window['bodies'] = array_slice($window['bodies'], -10);

        Cache::put($key, $window, $cfg['window_seconds'] + 5);

        $repeats = count(array_filter($window['bodies'], fn ($h) => $h === $hash));
        $flood = $window['count'] > $cfg['max_messages_per_window'];
        $repeatSpam = $repeats >= $cfg['repeat_threshold'];

        if ($flood || $repeatSpam) {
            $muteSeconds = $cfg['auto_mute_seconds'];
            $conversation->users()->updateExistingPivot($user->id, [
                'muted_until' => now()->addSeconds($muteSeconds),
                'muted_at' => now(),
            ]);
            $this->audit($conversation, null, 'anti_spam.auto_mute', $user->id, [
                'reason' => $flood ? 'flood' : 'repeat',
                'seconds' => $muteSeconds,
            ]);
            throw new \RuntimeException('Spam detected. You have been muted temporarily.');
        }
    }

    protected function trackSpamWindow(User $user, Conversation $conversation): void
    {
        // Window already updated in assertAntiSpam.
    }

    protected function assertMentionsAllowed(User $user, Conversation $conversation, string $body): void
    {
        $mentions = $this->parseMentions($body);
        if (in_array('everyone', $mentions, true)) {
            $this->permissions->assertCan($user, $conversation, 'mention_everyone');
        }
        if (in_array('admins', $mentions, true)) {
            $this->permissions->assertCan($user, $conversation, 'mention_admins');
        }
    }
}
