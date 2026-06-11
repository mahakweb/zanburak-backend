<?php

namespace App\Services\Messenger;

use App\Events\Messenger\MessengerBroadcast;
use App\Http\Resources\Messenger\MessageResource;
use App\Models\Contact;
use App\Models\ContactInvite;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessengerEvent;
use App\Models\User;
use App\Notifications\Channels\GhasedakChannel;
use App\Notifications\Messenger\InviteToZanburak;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class MessengerService
{
    public function __construct(
        protected RealtimeBus $bus
    ) {}

    // -------------------------------------------------------------------------
    // Conversations
    // -------------------------------------------------------------------------

    public function listConversations(User $user, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?: (int) config('messenger.conversations_per_page', 30);

        $paginator = Conversation::query()
            ->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                    ->whereNull('conversation_user.deleted_at');
            })
            ->with([
                'users:id,first_name,last_name,username,profile_pic,last_seen',
                'users.messengerSettings',
            ])
            ->orderByDesc('last_message_at')
            ->paginate($perPage);

        $previewLimit = (int) config('messenger.conversation_preview_messages', 30);

        foreach ($paginator->getCollection() as $conv) {
            $conv->unread_count = $conv->unreadCountFor($user);

            // Preload the most recent messages so the chat opens instantly
            // without waiting for a separate request.
            $preview = $this->recentMessagesFor($user, $conv, $previewLimit);
            $conv->setRelation('recentMessages', $preview['messages']);
            $conv->messages_has_more = $preview['has_more'];

            // The newest visible message doubles as the list preview, honoring
            // per-user cleared history & "delete for me".
            $conv->setRelation('lastMessage', $preview['messages']->last());
        }

        return $paginator;
    }

    /**
     * Base query for the messages a user is allowed to see in a conversation:
     * not soft-deleted, not hidden via "delete for me", and after the user's
     * cleared_at marker. Ordered newest-first for cursor pagination.
     */
    protected function visibleMessagesQuery(User $user, Conversation $conversation)
    {
        $pivot = $conversation->relationLoaded('users')
            ? $conversation->users->firstWhere('id', $user->id)?->pivot
            : null;
        $pivot ??= $conversation->users()->where('users.id', $user->id)->first()?->pivot;
        $clearedAt = $pivot?->cleared_at;

        $query = $conversation->messages()
            ->with($this->messageRelations())
            ->whereDoesntHave('deletedForUsers', fn ($q) => $q->where('users.id', $user->id))
            ->reorder('id', 'desc');

        if ($clearedAt) {
            $query->where('created_at', '>', $clearedAt);
        }

        return $query;
    }

    /**
     * The most recent message in a conversation that is still visible to the
     * given user.
     */
    public function lastVisibleMessageFor(User $user, Conversation $conversation): ?Message
    {
        return $this->visibleMessagesQuery($user, $conversation)->first();
    }

    /**
     * The latest $limit visible messages, returned chronologically (oldest
     * first) for direct rendering, plus a flag indicating older messages exist.
     *
     * @return array{messages: \Illuminate\Support\Collection, has_more: bool}
     */
    public function recentMessagesFor(User $user, Conversation $conversation, int $limit = 30): array
    {
        $rows = $this->visibleMessagesQuery($user, $conversation)
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;

        $messages = $rows->take($limit)->sortBy('id')->values();

        return ['messages' => $messages, 'has_more' => $hasMore];
    }

    public function findOrCreateConversation(User $me, int $otherUserId): Conversation
    {
        if ($otherUserId === $me->id) {
            throw new \InvalidArgumentException('Cannot create conversation with yourself');
        }

        $other = User::findOrFail($otherUserId);

        // Check block status
        $blocked = Contact::where('user_id', $me->id)
            ->where('contact_user_id', $otherUserId)
            ->where('is_blocked', true)
            ->exists();

        if ($blocked) {
            throw new \RuntimeException('You have blocked this user');
        }

        $blockedBy = Contact::where('user_id', $otherUserId)
            ->where('contact_user_id', $me->id)
            ->where('is_blocked', true)
            ->exists();

        if ($blockedBy) {
            throw new \RuntimeException('This user has blocked you');
        }

        $existing = Conversation::where('type', 'private')
            ->whereHas('users', fn ($q) => $q->where('users.id', $me->id))
            ->whereHas('users', fn ($q) => $q->where('users.id', $otherUserId))
            ->first();

        if ($existing) {
            // Restore visibility only for me. The other side manages their own
            // (and my cleared_at stays, so old history doesn't reappear).
            $existing->users()->updateExistingPivot($me->id, ['deleted_at' => null]);

            return $existing->load([
                'users:id,first_name,last_name,username,profile_pic,last_seen',
                'lastMessage',
            ]);
        }

        return DB::transaction(function () use ($me, $otherUserId) {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->users()->attach([$me->id, $otherUserId]);

            return $conversation->load([
                'users:id,first_name,last_name,username,profile_pic,last_seen',
            ]);
        });
    }

    /**
     * The user's personal "Saved Messages" chat: a single-participant
     * conversation of type 'saved' where a user can forward / keep notes.
     */
    public function getOrCreateSavedConversation(User $me): Conversation
    {
        $existing = Conversation::where('type', 'saved')
            ->whereHas('users', fn ($q) => $q->where('users.id', $me->id))
            ->first();

        if ($existing) {
            // Restore visibility in case the user had hidden it.
            $existing->users()->updateExistingPivot($me->id, ['deleted_at' => null]);

            return $existing->load([
                'users:id,first_name,last_name,username,profile_pic,last_seen',
                'lastMessage',
            ]);
        }

        return DB::transaction(function () use ($me) {
            $conversation = Conversation::create(['type' => 'saved']);
            $conversation->users()->attach([$me->id]);

            return $conversation->load([
                'users:id,first_name,last_name,username,profile_pic,last_seen',
            ]);
        });
    }

    public function deleteConversation(User $user, Conversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);

        $now = now();
        // Delete-for-me: hide from my list AND start fresh if it is reopened
        // (a new message restores it, but old history stays hidden for me).
        $conversation->users()->updateExistingPivot($user->id, [
            'deleted_at' => $now,
            'cleared_at' => $now,
        ]);

        // Sync removal to the user's other devices.
        $this->emitEvent($user->id, $conversation->id, 'conversation.deleted', [
            'conversation_id' => $conversation->id,
        ]);

        // Once every participant has deleted it, purge it from the database.
        $remaining = $conversation->users()
            ->whereNull('conversation_user.deleted_at')
            ->count();

        if ($remaining === 0) {
            DB::transaction(function () use ($conversation) {
                $conversation->update(['last_message_id' => null]);
                $conversation->messages()->forceDelete();
                $conversation->users()->detach();
                $conversation->delete();
            });
        }
    }

    public function clearConversation(User $user, Conversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);

        $now = now();
        // Clearing history wipes it for *both* participants (Telegram-style),
        // while attributing who performed the action.
        foreach ($conversation->users()->pluck('users.id') as $uid) {
            $conversation->users()->updateExistingPivot($uid, ['cleared_at' => $now]);
        }

        $clearedByName = trim("{$user->first_name} {$user->last_name}") ?: ($user->username ?: '');

        $this->notifyAllParticipants($conversation, 'conversation.cleared', [
            'conversation_id' => $conversation->id,
            'cleared_by' => $user->id,
            'cleared_by_name' => $clearedByName,
        ]);
    }

    public function muteConversation(User $user, Conversation $conversation, bool $mute = true): void
    {
        $this->assertParticipant($user, $conversation);
        $conversation->users()->updateExistingPivot($user->id, [
            'muted_at' => $mute ? now() : null,
        ]);

        $this->emitEvent($user->id, $conversation->id, 'conversation.muted', [
            'conversation_id' => $conversation->id,
            'muted' => $mute,
        ]);
    }

    // -------------------------------------------------------------------------
    // Messages
    // -------------------------------------------------------------------------

    public function getMessages(User $user, Conversation $conversation, ?int $beforeId = null): LengthAwarePaginator
    {
        $this->assertParticipant($user, $conversation);

        $query = $this->visibleMessagesQuery($user, $conversation);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $paginator = $query->paginate(config('messenger.messages_per_page', 40));

        // Return chronological order (oldest first) for chat UI
        $paginator->setCollection(
            $paginator->getCollection()->sortBy('id')->values()
        );

        return $paginator;
    }

    public function sendMessage(User $user, Conversation $conversation, string $body, ?string $clientId = null, array $options = []): Message
    {
        $this->assertParticipant($user, $conversation);
        $this->assertNotBlocked($user, $conversation);

        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Message body cannot be empty');
        }

        $maxLen = config('messenger.max_message_length', 5000);
        if (mb_strlen($body) > $maxLen) {
            throw new \InvalidArgumentException("Message exceeds maximum length of {$maxLen} characters");
        }

        // Idempotent send via client_id
        if ($clientId) {
            $existing = Message::where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->where('client_id', $clientId)
                ->first();

            if ($existing) {
                return $existing->load($this->messageRelations());
            }
        }

        // Validate the quoted message belongs to the same conversation
        $replyToId = $options['reply_to_id'] ?? null;
        if ($replyToId) {
            $valid = Message::where('id', $replyToId)
                ->where('conversation_id', $conversation->id)
                ->exists();
            if (! $valid) {
                $replyToId = null;
            }
        }

        $attributes = [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'client_id' => $clientId ?? Str::uuid()->toString(),
            'body' => $body,
            'type' => 'text',
            'reply_to_id' => $replyToId,
            'reply_show_title' => $options['reply_show_title'] ?? true,
            'forwarded_from_user_id' => $options['forwarded_from_user_id'] ?? null,
        ];

        return DB::transaction(function () use ($user, $conversation, $attributes) {
            $message = Message::create($attributes);

            $conversation->update([
                'last_message_id' => $message->id,
                'last_message_at' => now(),
            ]);

            // Restore conversation for all participants who had deleted it
            $conversation->users()->whereNotNull('conversation_user.deleted_at')
                ->update(['conversation_user.deleted_at' => null]);

            $message->load($this->messageRelations());

            // Notify every participant — including the sender's *other* devices
            // so a chat stays in sync across multiple logged-in sessions.
            $this->notifyAllParticipants($conversation, 'message.new', [
                'message' => (new MessageResource($message))->resolve(),
            ]);

            return $message;
        });
    }

    /**
     * Forward a set of messages into a target conversation as new messages,
     * preserving the original author via forwarded_from_user_id.
     *
     * @param  int[]  $messageIds
     * @return Message[]
     */
    public function forwardMessages(User $user, array $messageIds, Conversation $target, bool $dropAuthor = false): array
    {
        $this->assertParticipant($user, $target);

        $sources = Message::whereIn('id', $messageIds)
            ->whereHas('conversation.users', fn ($q) => $q->where('users.id', $user->id))
            ->orderBy('id')
            ->get();

        $created = [];
        foreach ($sources as $source) {
            // When $dropAuthor is true we forward "without quote": the message is
            // re-sent as if authored by the forwarder, with no original-author header.
            $options = $dropAuthor
                ? []
                : ['forwarded_from_user_id' => $source->forwarded_from_user_id ?: $source->user_id];

            $created[] = $this->sendMessage($user, $target, $source->body, null, $options);
        }

        return $created;
    }

    public function editMessage(User $user, Message $message, string $body): Message
    {
        if (! $message->isOwnedBy($user)) {
            throw new \RuntimeException('You can only edit your own messages');
        }

        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Message body cannot be empty');
        }

        $message->update([
            'body' => $body,
            'edited_at' => now(),
        ]);

        $message->load($this->messageRelations());

        // Notify everyone, including the editor's other devices.
        $this->notifyAllParticipants(
            $message->conversation,
            'message.updated',
            ['message' => (new MessageResource($message))->resolve()]
        );

        return $message;
    }

    /**
     * Delete a message.
     *
     * @param  string  $scope  'everyone' (hard/soft delete for all, owner only)
     *                         or 'me' (hide from this user's view only).
     */
    public function deleteMessage(User $user, Message $message, string $scope = 'everyone'): void
    {
        $conversation = $message->conversation;
        $this->assertParticipant($user, $conversation);

        $conversationId = $message->conversation_id;
        $messageId = $message->id;

        if ($scope === 'me') {
            // Hide only from this user; sync to their own devices.
            $message->deletedForUsers()->syncWithoutDetaching([$user->id]);

            // Drop it from this user's pinned list too.
            \App\Models\MessagePin::where('message_id', $messageId)
                ->where('user_id', $user->id)
                ->delete();

            $this->emitEvent($user->id, $conversationId, 'message.deleted', [
                'message_id' => $messageId,
                'conversation_id' => $conversationId,
                'scope' => 'me',
            ]);

            return;
        }

        // scope === everyone: only the author may remove for both sides.
        if (! $message->isOwnedBy($user)) {
            throw new \RuntimeException('You can only delete your own messages for everyone');
        }

        // Pins reference this message; clear them for all participants.
        \App\Models\MessagePin::where('message_id', $messageId)->delete();

        $message->delete();

        $this->notifyAllParticipants($conversation, 'message.deleted', [
            'message_id' => $messageId,
            'conversation_id' => $conversationId,
            'scope' => 'everyone',
        ]);
    }

    /**
     * Delete several messages at once.
     *
     * @param  int[]  $messageIds
     * @return int  Number of messages actually deleted
     */
    public function bulkDeleteMessages(User $user, array $messageIds, string $scope = 'everyone'): int
    {
        $query = Message::whereIn('id', $messageIds)
            ->whereHas('conversation.users', fn ($q) => $q->where('users.id', $user->id));

        // "delete for everyone" is restricted to the user's own messages.
        if ($scope !== 'me') {
            $query->where('user_id', $user->id);
        }

        $messages = $query->get();

        $count = 0;
        foreach ($messages as $message) {
            $this->deleteMessage($user, $message, $scope);
            $count++;
        }

        return $count;
    }

    public function markRead(User $user, Conversation $conversation): int
    {
        $this->assertParticipant($user, $conversation);

        $updated = Message::where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $conversation->users()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        if ($updated > 0) {
            // Notify everyone: the partner sees read ticks, and the reader's
            // other devices reset their unread badge.
            $this->notifyAllParticipants(
                $conversation,
                'messages.read',
                [
                    'conversation_id' => $conversation->id,
                    'reader_id' => $user->id,
                    'count' => $updated,
                ]
            );
        }

        return $updated;
    }

    public function sendTyping(User $user, Conversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);

        if ($this->bus->isTyping($conversation->id, $user->id)) {
            return;
        }

        $this->bus->setTyping($conversation->id, $user->id);

        $this->notifyConversationParticipants(
            $conversation,
            $user->id,
            'typing',
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'username' => $user->username,
                ],
            ],
            false
        );
    }

    // -------------------------------------------------------------------------
    // Pinned messages
    // -------------------------------------------------------------------------

    /**
     * Messages pinned in this conversation for the given user, oldest first.
     */
    public function pinnedMessagesFor(User $user, Conversation $conversation): Collection
    {
        $this->assertParticipant($user, $conversation);

        $ids = \App\Models\MessagePin::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->orderBy('message_id')
            ->pluck('message_id');

        if ($ids->isEmpty()) {
            return new Collection();
        }

        return Message::whereIn('id', $ids)
            ->with($this->messageRelations())
            ->orderBy('id')
            ->get();
    }

    /**
     * Pin a message for myself, or for every participant when $forEveryone.
     */
    public function pinMessage(User $user, Message $message, bool $forEveryone = false): void
    {
        $conversation = $message->conversation;
        $this->assertParticipant($user, $conversation);

        $recipients = $forEveryone
            ? $conversation->users()->pluck('users.id')->all()
            : [$user->id];

        foreach ($recipients as $uid) {
            \App\Models\MessagePin::firstOrCreate(
                ['message_id' => $message->id, 'user_id' => $uid],
                ['conversation_id' => $conversation->id, 'pinned_by_id' => $user->id]
            );
        }

        $message->load($this->messageRelations());
        $resource = (new MessageResource($message))->resolve();

        foreach ($recipients as $uid) {
            $this->emitEvent($uid, $conversation->id, 'message.pinned', [
                'conversation_id' => $conversation->id,
                'message' => $resource,
            ]);
        }
    }

    /**
     * Remove a message from my own pinned list.
     */
    public function unpinMessage(User $user, Message $message): void
    {
        $conversation = $message->conversation;
        $this->assertParticipant($user, $conversation);

        \App\Models\MessagePin::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->delete();

        $this->emitEvent($user->id, $conversation->id, 'message.unpinned', [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
        ]);
    }

    /**
     * Clear my entire pinned list for a conversation.
     */
    public function unpinAll(User $user, Conversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);

        \App\Models\MessagePin::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->delete();

        $this->emitEvent($user->id, $conversation->id, 'messages.unpinned_all', [
            'conversation_id' => $conversation->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Contacts
    // -------------------------------------------------------------------------

    public function listContacts(User $user): Collection
    {
        return Contact::where('user_id', $user->id)
            ->with('contactUser:id,first_name,last_name,username,profile_pic,last_seen')
            ->orderByDesc('is_favorite')
            ->orderBy('name')
            ->get();
    }

    public function addContact(User $user, int $contactUserId, ?string $name = null): Contact
    {
        if ($contactUserId === $user->id) {
            throw new \InvalidArgumentException('Cannot add yourself as a contact');
        }

        $contactUser = User::findOrFail($contactUserId);

        $contact = Contact::firstOrCreate(
            ['user_id' => $user->id, 'contact_user_id' => $contactUserId],
            ['name' => $name ?? trim("{$contactUser->first_name} {$contactUser->last_name}") ?: $contactUser->username]
        );

        return $contact->load('contactUser:id,first_name,last_name,username,profile_pic,last_seen');
    }

    public function updateContact(User $user, Contact $contact, array $data): Contact
    {
        if ((int) $contact->user_id !== (int) $user->id) {
            throw new \RuntimeException('Forbidden');
        }

        $contact->update(array_intersect_key($data, array_flip(['name', 'is_blocked', 'is_favorite'])));

        return $contact->load('contactUser:id,first_name,last_name,username,profile_pic,last_seen');
    }

    public function deleteContact(User $user, Contact $contact): void
    {
        if ((int) $contact->user_id !== (int) $user->id) {
            throw new \RuntimeException('Forbidden');
        }

        $contact->delete();
    }

    public function searchUsers(User $user, string $query, int $limit = 10): Collection
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return new Collection();
        }

        return User::where('id', '!=', $user->id)
            ->where('active', true)
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%");
            })
            ->select('id', 'first_name', 'last_name', 'username', 'profile_pic', 'last_seen')
            ->limit($limit)
            ->get();
    }

    // -------------------------------------------------------------------------
    // Realtime events
    // -------------------------------------------------------------------------

    /**
     * Deliver a realtime event to a single user.
     *
     * Primary transport is Laravel Reverb (broadcast). Durable events are also
     * persisted to the DB so a reconnecting client can replay them via /sync.
     * High-frequency, ephemeral events (typing) skip persistence.
     * Broadcasting is best-effort: a Reverb outage never breaks the REST flow.
     */
    public function emitEvent(int $userId, ?int $conversationId, string $type, array $payload = [], bool $persist = true): void
    {
        $eventId = 0;

        if ($persist) {
            $event = MessengerEvent::create([
                'user_id' => $userId,
                'conversation_id' => $conversationId,
                'type' => $type,
                'payload' => $payload,
                'created_at' => now(),
            ]);
            $eventId = $event->id;
        }

        try {
            broadcast(new MessengerBroadcast($userId, $type, $payload, $eventId));
        } catch (\Throwable $e) {
            Log::warning('Messenger broadcast failed: '.$e->getMessage());
        }
    }

    public function getEventsSince(int $userId, int $sinceId, int $limit = 50): Collection
    {
        return MessengerEvent::where('user_id', $userId)
            ->where('id', '>', $sinceId)
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function getLatestCursor(int $userId): int
    {
        $cached = $this->bus->getCachedCursor($userId);
        if ($cached !== null) {
            return $cached;
        }

        return (int) (MessengerEvent::where('user_id', $userId)->max('id') ?? 0);
    }

    public function totalUnreadCount(User $user): int
    {
        return (int) DB::table('messages')
            ->join('conversation_user', function ($join) use ($user) {
                $join->on('messages.conversation_id', '=', 'conversation_user.conversation_id')
                    ->where('conversation_user.user_id', '=', $user->id);
            })
            ->where('messages.user_id', '!=', $user->id)
            ->whereNull('messages.read_at')
            ->whereNull('messages.deleted_at')
            ->whereNull('conversation_user.deleted_at')
            ->count();
    }

    public function pruneOldEvents(): int
    {
        $hours = config('messenger.event_retention_hours', 48);

        return MessengerEvent::where('created_at', '<', now()->subHours($hours))->delete();
    }

    // -------------------------------------------------------------------------
    // Settings & Profile
    // -------------------------------------------------------------------------

    public function getSettings(User $user): \App\Models\MessengerSetting
    {
        return \App\Models\MessengerSetting::firstOrCreate(
            ['user_id' => $user->id],
            \App\Models\MessengerSetting::defaults()
        );
    }

    public function updateSettings(User $user, array $data): \App\Models\MessengerSetting
    {
        $settings = $this->getSettings($user);

        $before = [
            'show_online' => (bool) $settings->show_online,
            'show_last_seen' => (bool) $settings->show_last_seen,
        ];

        $settings->update(array_intersect_key($data, array_flip([
            'enter_to_send', 'quote_with_title', 'forward_tap_to_chat', 'wallpaper', 'theme', 'locale',
            'show_online', 'show_last_seen', 'show_phone', 'show_email',
        ])));

        // Privacy preferences that change what *others* see (online state &
        // last seen) must propagate in realtime to everyone in contact.
        $privacyChanged = (bool) $settings->show_online !== $before['show_online']
            || (bool) $settings->show_last_seen !== $before['show_last_seen'];

        if ($privacyChanged) {
            // Make sure broadcastPresence resolves the freshly-saved settings.
            $user->setRelation('messengerSettings', $settings);
            $this->broadcastPresence($user, $user->isOnline());
        }

        return $settings;
    }

    public function updateMyProfile(User $user, array $data): User
    {
        $user->update(array_intersect_key($data, array_flip([
            'first_name', 'last_name', 'username', 'bio',
        ])));

        return $user->refresh();
    }

    /**
     * Public-ish profile of any active user (for the "view profile" sheet).
     */
    public function getUserProfile(User $viewer, int $userId): User
    {
        return User::where('id', $userId)
            ->where('active', true)
            ->select('id', 'first_name', 'last_name', 'username', 'profile_pic', 'cover_pic', 'bio', 'last_seen', 'email', 'mobile')
            ->with('messengerSettings')
            ->firstOrFail();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Eager-load set used everywhere a message is serialized.
     */
    protected function messageRelations(): array
    {
        return [
            'user:id,first_name,last_name,username,profile_pic,last_seen',
            'forwardedFromUser:id,first_name,last_name,username,profile_pic,last_seen',
            'forwardedFromUser.messengerSettings',
            'replyTo' => fn ($q) => $q->with('user:id,first_name,last_name,username,profile_pic,last_seen'),
        ];
    }

    protected function assertParticipant(User $user, Conversation $conversation): void
    {
        if (! $conversation->hasParticipant($user)) {
            throw new \RuntimeException('Forbidden');
        }
    }

    protected function notifyConversationParticipants(
        Conversation $conversation,
        int $excludeUserId,
        string $type,
        array $payload,
        bool $persist = true
    ): void {
        $participants = $conversation->users()->where('users.id', '!=', $excludeUserId)->get();
        foreach ($participants as $recipient) {
            $this->emitEvent($recipient->id, $conversation->id, $type, $payload, $persist);
        }
    }

    /**
     * Notify *every* participant of a conversation, including the actor's own
     * other devices. Enables multi-device realtime sync.
     */
    protected function notifyAllParticipants(
        Conversation $conversation,
        string $type,
        array $payload,
        bool $persist = true
    ): void {
        $participants = $conversation->users()->get();
        foreach ($participants as $recipient) {
            $this->emitEvent($recipient->id, $conversation->id, $type, $payload, $persist);
        }
    }

    /**
     * Reject sending if either side has blocked the other.
     */
    protected function assertNotBlocked(User $user, Conversation $conversation): void
    {
        $other = $conversation->otherUser($user)
            ?? $conversation->users()->where('users.id', '!=', $user->id)->first();

        if (! $other) {
            return;
        }

        $iBlocked = Contact::where('user_id', $user->id)
            ->where('contact_user_id', $other->id)
            ->where('is_blocked', true)
            ->exists();

        if ($iBlocked) {
            throw new \RuntimeException('You have blocked this user');
        }

        $blockedByThem = Contact::where('user_id', $other->id)
            ->where('contact_user_id', $user->id)
            ->where('is_blocked', true)
            ->exists();

        if ($blockedByThem) {
            throw new \RuntimeException('This user has blocked you');
        }
    }

    // -------------------------------------------------------------------------
    // Presence
    // -------------------------------------------------------------------------

    /**
     * Users who should be told about my presence changes: conversation
     * partners plus people who have me in their contacts.
     *
     * @return int[]
     */
    protected function presenceAudience(User $user): array
    {
        $partnerIds = DB::table('conversation_user as cu_me')
            ->join('conversation_user as cu_other', 'cu_me.conversation_id', '=', 'cu_other.conversation_id')
            ->where('cu_me.user_id', $user->id)
            ->where('cu_other.user_id', '!=', $user->id)
            ->pluck('cu_other.user_id');

        $contactOwners = Contact::where('contact_user_id', $user->id)->pluck('user_id');

        return $partnerIds->merge($contactOwners)->unique()->values()->all();
    }

    /**
     * Heartbeat: refresh last_seen and broadcast that I'm online (respecting
     * my privacy preference).
     */
    public function pingPresence(User $user): void
    {
        $user->forceFill(['last_seen' => now()])->saveQuietly();

        $this->broadcastPresence($user, true);
    }

    /**
     * Mark me offline now (called when leaving the messenger).
     */
    public function setOffline(User $user): void
    {
        $this->broadcastPresence($user, false);
    }

    protected function broadcastPresence(User $user, bool $online): void
    {
        $settings = $user->resolvedMessengerSettings();
        $isOnline = $online && $settings->show_online;
        $lastSeen = null;
        if ($settings->show_last_seen) {
            $lastSeen = $user->last_seen
                ? \Illuminate\Support\Carbon::parse($user->last_seen)->toIso8601String()
                : now()->toIso8601String();
        }

        $payload = [
            'user_id' => $user->id,
            'is_online' => $isOnline,
            'last_seen' => $lastSeen,
        ];

        foreach ($this->presenceAudience($user) as $audienceId) {
            // Presence is ephemeral; don't persist to the durable event log.
            $this->emitEvent($audienceId, null, 'presence', $payload, false);
        }
    }

    // -------------------------------------------------------------------------
    // Blocking
    // -------------------------------------------------------------------------

    public function listBlocked(User $user): Collection
    {
        return Contact::where('user_id', $user->id)
            ->where('is_blocked', true)
            ->with('contactUser:id,first_name,last_name,username,profile_pic,last_seen')
            ->orderBy('name')
            ->get();
    }

    public function blockUser(User $user, int $targetUserId): Contact
    {
        if ($targetUserId === $user->id) {
            throw new \InvalidArgumentException('Cannot block yourself');
        }

        $target = User::findOrFail($targetUserId);

        $contact = Contact::firstOrCreate(
            ['user_id' => $user->id, 'contact_user_id' => $targetUserId],
            ['name' => trim("{$target->first_name} {$target->last_name}") ?: $target->username]
        );
        $contact->update(['is_blocked' => true]);

        return $contact->load('contactUser:id,first_name,last_name,username,profile_pic,last_seen');
    }

    public function unblockUser(User $user, int $targetUserId): void
    {
        Contact::where('user_id', $user->id)
            ->where('contact_user_id', $targetUserId)
            ->update(['is_blocked' => false]);
    }

    // -------------------------------------------------------------------------
    // Contact lookup & invitations
    // -------------------------------------------------------------------------

    /**
     * Find a user by exact email, mobile, or username.
     */
    public function findUserByIdentifier(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $normalizedMobile = $this->normalizeMobile($identifier);

        return User::query()
            ->where('active', true)
            ->where(function ($q) use ($identifier, $normalizedMobile) {
                $q->where('email', $identifier)
                    ->orWhere('username', ltrim($identifier, '@'));
                if ($normalizedMobile) {
                    $q->orWhere('mobile', $normalizedMobile);
                }
            })
            ->first();
    }

    public function detectIdentifierChannel(string $identifier): ?string
    {
        $identifier = trim($identifier);
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        if ($this->normalizeMobile($identifier)) {
            return 'sms';
        }

        return null;
    }

    protected function normalizeMobile(string $value): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', $value);
        if ($digits === null || $digits === '') {
            return null;
        }
        // Iranian mobile normalization: 0098/+98/98 → 0XXXXXXXXXX
        if (str_starts_with($digits, '0098')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (strlen($digits) === 10 && $digits[0] === '9') {
            $digits = '0'.$digits;
        }

        return preg_match('/^09\d{9}$/', $digits) ? $digits : null;
    }

    /**
     * Send an invitation to a non-registered email/phone, throttled per target.
     */
    public function sendInvite(User $inviter, string $identifier, string $channel): array
    {
        $identifier = trim($identifier);
        $inviterName = trim("{$inviter->first_name} {$inviter->last_name}") ?: ($inviter->username ?: 'A Zanburak user');
        $registerUrl = rtrim((string) (config('app.frontend_url') ?: config('app.url') ?: 'https://zanburak.ir'), '/').'/auth';

        $invite = ContactInvite::firstOrNew([
            'inviter_id' => $inviter->id,
            'identifier' => $identifier,
            'channel' => $channel,
        ]);

        // Throttle: at most one invite per target per 24h.
        if ($invite->exists && $invite->last_sent_at && $invite->last_sent_at->gt(now()->subDay())) {
            return ['invited' => true, 'throttled' => true];
        }

        try {
            if ($channel === 'email') {
                Notification::route('mail', $identifier)
                    ->notify(new InviteToZanburak($inviterName, $registerUrl, 'email'));
            } else {
                $mobile = $this->normalizeMobile($identifier) ?? $identifier;
                (new GhasedakChannel)->send(null, new InviteToZanburak($inviterName, $registerUrl, 'sms', $mobile));
            }
        } catch (\Throwable $e) {
            Log::warning('Messenger invite failed: '.$e->getMessage());

            return ['invited' => false, 'error' => true];
        }

        $invite->last_sent_at = now();
        $invite->send_count = ($invite->send_count ?? 0) + 1;
        $invite->save();

        return ['invited' => true];
    }
}
