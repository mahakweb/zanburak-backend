<?php

namespace App\Services\Messenger;

use App\Events\Messenger\MessengerBroadcast;
use App\Http\Resources\Messenger\MessageResource;
use App\Jobs\DeleteMessengerMedia;
use App\Models\Contact;
use App\Models\ContactInvite;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessagePin;
use App\Models\MessengerConversationWallpaper;
use App\Models\MessengerEvent;
use App\Models\MessengerMedia;
use App\Models\MessengerWallpaper;
use App\Models\SyncedContact;
use App\Models\User;
use App\Notifications\Channels\GhasedakChannel;
use App\Notifications\Messenger\InviteToZanburak;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MessengerService
{
    public function __construct(
        protected RealtimeBus $bus,
        protected MessengerOutbox $outbox,
        protected PrivacyService $privacy
    ) {}

    // -------------------------------------------------------------------------
    // Conversations
    // -------------------------------------------------------------------------

    public function listConversations(User $user, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage = $perPage ?: (int) config('messenger.conversations_per_page', 30);

        $paginator = Conversation::query()
            ->where(function ($outer) use ($user) {
                $outer->where(function ($direct) use ($user) {
                    $direct->whereNotIn('type', [Conversation::TYPE_GROUP, Conversation::TYPE_CHANNEL])
                        ->whereHas('users', function ($q) use ($user) {
                            $q->where('users.id', $user->id)
                                ->whereNull('conversation_user.deleted_at');
                        })
                        // Hide empty private chats until the first message is sent.
                        // Saved Messages and other non-private types stay listed.
                        ->where(function ($empty) {
                            $empty->where('type', '!=', Conversation::TYPE_PRIVATE)
                                ->orWhereNotNull('last_message_id')
                                ->orWhereNotNull('last_message_at');
                        });
                })->orWhere(function ($community) use ($user) {
                    $community->whereIn('type', [Conversation::TYPE_GROUP, Conversation::TYPE_CHANNEL])
                        ->whereHas('users', function ($q) use ($user) {
                            $q->where('users.id', $user->id)
                                ->whereNull('conversation_user.deleted_at')
                                ->where(function ($flags) {
                                    $flags->where('conversation_user.is_active', true)
                                        ->orWhereNull('conversation_user.is_active');
                                })
                                ->where(function ($flags) {
                                    $flags->where('conversation_user.is_banned', false)
                                        ->orWhereNull('conversation_user.is_banned');
                                });
                        });
                });
            })
            ->with([
                'users:id,first_name,last_name,username,profile_pic,last_seen',
                'users.messengerSettings',
            ])
            ->orderByDesc('last_message_at')
            ->paginate($perPage);

        $conversationIds = $paginator->getCollection()->pluck('id');
        $latestIds = Message::query()
            ->visibleTo($user)
            ->whereIn('conversation_id', $conversationIds)
            ->selectRaw('MAX(id) as id')
            ->groupBy('conversation_id')
            ->pluck('id');
        $latestByConversation = Message::query()
            ->visibleTo($user)
            ->whereIn('id', $latestIds)
            ->with($this->messageRelations($user))
            ->get()
            ->keyBy('conversation_id');
        $unreadByConversation = Message::query()
            ->visibleTo($user)
            ->whereIn('messages.conversation_id', $conversationIds)
            ->where('messages.user_id', '!=', $user->id)
            ->whereNull('messages.read_at')
            ->join('conversation_user', function ($join) use ($user) {
                $join->on('conversation_user.conversation_id', '=', 'messages.conversation_id')
                    ->where('conversation_user.user_id', '=', $user->id)
                    ->whereNull('conversation_user.deleted_at');
            })
            ->where(function ($q) {
                $q->whereNull('conversation_user.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'conversation_user.last_read_at');
            })
            ->selectRaw('messages.conversation_id, COUNT(*) as aggregate')
            ->groupBy('messages.conversation_id')
            ->pluck('aggregate', 'conversation_id');

        $recentLimit = (int) config('messenger.recent_messages_in_list', 50);

        // Collect hot unread ids once, then exclude any already persisted in DB
        // so the same message_id cannot double-count during flush races.
        $hotUnreadByConversation = [];
        $hotUnreadIdSet = [];
        if ($this->outbox->isActive()) {
            foreach ($conversationIds as $cid) {
                foreach ($this->outbox->hotMessagesForConversation((int) $cid) as $row) {
                    if ((int) ($row['user_id'] ?? 0) === (int) $user->id) {
                        continue;
                    }
                    if (! empty($row['read_at'])) {
                        continue;
                    }
                    $hid = (int) ($row['id'] ?? 0);
                    if ($hid <= 0) {
                        continue;
                    }
                    $hotUnreadByConversation[(int) $cid][] = $hid;
                    $hotUnreadIdSet[$hid] = true;
                }
            }
        }
        $hotIdsAlreadyInDb = [];
        if ($hotUnreadIdSet !== []) {
            $hotIdsAlreadyInDb = array_fill_keys(
                Message::query()
                    ->whereIn('id', array_keys($hotUnreadIdSet))
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all(),
                true
            );
        }

        $peerLastReadByConversation = DB::table('conversation_user')
            ->select('conversation_id', DB::raw('MAX(last_read_at) as peer_last_read_at'))
            ->whereIn('conversation_id', $conversationIds)
            ->where('user_id', '!=', $user->id)
            ->whereNull('deleted_at')
            ->whereNotNull('last_read_at')
            ->groupBy('conversation_id')
            ->pluck('peer_last_read_at', 'conversation_id');

        foreach ($paginator->getCollection() as $conversation) {
            // Warm Redis membership for WS auth + DB-free sends.
            if ($conversation->relationLoaded('users')) {
                $this->outbox->cacheParticipantIds(
                    (int) $conversation->id,
                    $conversation->users->pluck('id')->map(fn ($id) => (int) $id)->all()
                );
            }

            $latest = $latestByConversation->get($conversation->id);
            $recent = $this->recentMessagesFor($user, $conversation, $recentLimit);

            // Merge not-yet-flushed hot messages so list + history stay in sync
            // when the live websocket was missed.
            if ($this->outbox->isActive()) {
                $hot = $this->rejectClearedHotRows(
                    $user,
                    $conversation,
                    collect($this->outbox->hotMessagesForConversation($conversation->id))
                        ->map(fn (array $row) => $this->hydrateMessageRow($user, $row))
                );
                if ($hot->isNotEmpty()) {
                    $byId = $recent['messages']->keyBy('id');
                    foreach ($hot as $msg) {
                        if (! $byId->has($msg->id)) {
                            $recent['messages']->push($msg);
                        }
                    }
                    $recent['messages'] = $recent['messages']->sortBy('id')->values();
                    if ($recent['messages']->count() > $recentLimit) {
                        $recent['has_more'] = true;
                        $recent['messages'] = $recent['messages']->slice(-$recentLimit)->values();
                    }
                    $hotLatest = $recent['messages']->last();
                    if ($hotLatest && (! $latest || (int) $hotLatest->id > (int) $latest->id)) {
                        $latest = $hotLatest;
                    }
                }
            }

            $unread = (int) ($unreadByConversation[$conversation->id] ?? 0);
            foreach ($hotUnreadByConversation[(int) $conversation->id] ?? [] as $hid) {
                if (isset($hotIdsAlreadyInDb[$hid])) {
                    continue;
                }
                $unread++;
            }

            // Apply peer last_read receipts to list seed / last_message ticks.
            $peerReadAt = $peerLastReadByConversation[$conversation->id] ?? null;
            if ($recent['messages']->isNotEmpty()) {
                $recent['messages'] = $this->applyPeerReadReceipts(
                    $user,
                    $conversation,
                    $recent['messages'],
                    $peerReadAt
                );
            }
            if ($latest instanceof Message) {
                $latest = $this->applyPeerReadReceipts(
                    $user,
                    $conversation,
                    collect([$latest]),
                    $peerReadAt
                )->first();
            }

            $conversation->setResponseAttribute('unread_count', $unread);
            $conversation->setRelation('lastMessage', $latest);
            $conversation->setRelation('recentMessages', $recent['messages']);
            $conversation->setResponseAttribute('messages_has_more', $recent['has_more']);

            if ($conversation->isCommunity()) {
                $conversation->setResponseAttribute('my_role', $conversation->memberRole($user));
            }
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
        return Message::query()
            ->visibleTo($user)
            ->where('conversation_id', $conversation->id)
            ->with($this->messageRelations($user))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
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
     * Per-user history cutoff from conversation_user.cleared_at (if any).
     */
    protected function clearedAtFor(User $user, Conversation $conversation): ?\Carbon\CarbonInterface
    {
        $pivot = null;
        if ($conversation->relationLoaded('users')) {
            $pivot = $conversation->users->firstWhere('id', $user->id)?->pivot;
        }
        if (! $pivot) {
            $pivot = $conversation->users()->where('users.id', $user->id)->first()?->pivot;
        }
        $raw = $pivot?->cleared_at ?? null;
        if (! $raw) {
            return null;
        }

        return $raw instanceof \Carbon\CarbonInterface
            ? $raw
            : \Carbon\Carbon::parse($raw);
    }

    /**
     * Hot Redis rows are not covered by Message::visibleTo — enforce cleared_at.
     *
     * @param  iterable<int, Message|array>  $rows
     * @return \Illuminate\Support\Collection<int, Message|array>
     */
    protected function rejectClearedHotRows(User $user, Conversation $conversation, iterable $rows): \Illuminate\Support\Collection
    {
        $cutoff = $this->clearedAtFor($user, $conversation);
        $collection = collect($rows);
        if (! $cutoff) {
            return $collection->values();
        }

        return $collection->filter(function ($row) use ($cutoff) {
            $created = is_array($row)
                ? ($row['created_at'] ?? null)
                : ($row->created_at ?? null);
            if (! $created) {
                return false;
            }
            $at = $created instanceof \Carbon\CarbonInterface
                ? $created
                : \Carbon\Carbon::parse($created);

            return $at->gt($cutoff);
        })->values();
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

        $messages = $rows->take($limit)
            ->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

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

            $existing->load([
                'users:id,first_name,last_name,username,profile_pic,last_seen',
                'users.messengerSettings',
            ]);
            $existing->setRelation('lastMessage', $this->lastVisibleMessageFor($me, $existing));

            return $existing;
        }

        return DB::transaction(function () use ($me, $otherUserId) {
            $conversation = Conversation::create(['type' => 'private']);
            $conversation->users()->attach([$me->id, $otherUserId]);

            return $conversation->load([
                'users:id,first_name,last_name,username,profile_pic,last_seen',
                'users.messengerSettings',
            ]);
        });
    }

    /**
     * The user's personal "Saved Messages" chat: a single-participant
     * conversation of type 'saved' where a user can forward / keep notes.
     * First-class cloud self-chat (Telegram-style) — not an E2E peer chat.
     */
    public function getOrCreateSavedConversation(User $me): Conversation
    {
        return DB::transaction(function () use ($me) {
            // Serialize concurrent opens so we never create duplicate saved rows.
            User::query()->whereKey($me->id)->lockForUpdate()->first();

            $existing = Conversation::query()
                ->where('type', Conversation::TYPE_SAVED)
                ->where(function ($q) use ($me) {
                    $q->where('owner_id', $me->id)
                        ->orWhereHas('users', fn ($uq) => $uq->where('users.id', $me->id));
                })
                ->orderByRaw('case when owner_id = ? then 0 else 1 end', [$me->id])
                ->orderBy('id')
                ->first();

            if ($existing) {
                if ((int) ($existing->owner_id ?? 0) !== (int) $me->id) {
                    $existing->forceFill(['owner_id' => $me->id])->saveQuietly();
                }

                // Restore list visibility only. Never wipe cleared_at — intentional
                // Clear history must survive swipe-hide + reopen / forward.
                $pivot = $existing->users()->where('users.id', $me->id)->first()?->pivot;
                if (! $pivot) {
                    $existing->users()->attach([$me->id]);
                } else {
                    $existing->users()->updateExistingPivot($me->id, [
                        'deleted_at' => null,
                    ]);
                }

                $existing->load([
                    'users:id,first_name,last_name,username,profile_pic,last_seen',
                    'users.messengerSettings',
                ]);
                $existing->setRelation('lastMessage', $this->lastVisibleMessageFor($me, $existing));
                $this->warmParticipantCache($existing);

                return $existing;
            }

            $conversation = Conversation::create([
                'type' => Conversation::TYPE_SAVED,
                'owner_id' => $me->id,
            ]);
            $conversation->users()->attach([$me->id]);

            $conversation->load([
                'users:id,first_name,last_name,username,profile_pic,last_seen',
                'users.messengerSettings',
            ]);
            $this->warmParticipantCache($conversation);

            return $conversation;
        });
    }

    public function deleteConversation(User $user, Conversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);

        $now = now();
        // Saved Messages is a durable self-chat (Telegram-style): swipe-delete only
        // hides it from the list. History must survive so reopening from the menu
        // restores the same notes / forwarded media.
        if ($conversation->isSaved()) {
            $conversation->users()->updateExistingPivot($user->id, [
                'deleted_at' => $now,
            ]);
            $this->emitEvent($user->id, $conversation->id, 'conversation.deleted', [
                'conversation_id' => $conversation->id,
            ]);

            return;
        }

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
            $this->purgeConversationWithMedia($conversation);
        }
    }

    /**
     * Permanently remove a conversation after every participant has deleted it.
     * Releases shared media via live reference counts (forwards/stickers stay
     * until no message still points at the blob).
     */
    protected function purgeConversationWithMedia(Conversation $conversation): void
    {
        DB::transaction(function () use ($conversation) {
            $conversation->update(['last_message_id' => null]);

            $mediaIds = Message::withTrashed()
                ->where('conversation_id', $conversation->id)
                ->whereNotNull('media_id')
                ->pluck('media_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            Message::withTrashed()
                ->where('conversation_id', $conversation->id)
                ->forceDelete();

            foreach ($mediaIds as $mediaId) {
                $this->releaseMediaIfUnreferenced($mediaId);
            }

            $conversation->users()->detach();
            $conversation->delete();
        });
    }

    /**
     * Soft-delete a media row and enqueue blob cleanup when nothing live
     * references it. Safe to call inside an open DB transaction (disk delete
     * runs after commit).
     */
    protected function releaseMediaIfUnreferenced(int $mediaId, ?string $fallbackType = null): void
    {
        $media = MessengerMedia::query()->find($mediaId);
        if (! $media) {
            return;
        }

        $live = $media->liveReferenceCount();
        if ($live > 0) {
            // Keep ref_count aligned with reality after force-deletes / races.
            if ((int) $media->ref_count !== $live) {
                $media->forceFill(['ref_count' => $live])->saveQuietly();
            }

            return;
        }

        $meta = $media->toMessageMeta();
        $type = $fallbackType
            ?: (in_array($media->kind, Message::MEDIA_TYPES, true) ? $media->kind : 'file');
        $media->delete();

        DB::afterCommit(function () use ($meta, $type, $mediaId) {
            DeleteMessengerMedia::dispatch($meta, $type, $mediaId);
        });
    }

    /**
     * Clear history for everyone: permanently wipe messages (Postgres + Redis)
     * while keeping the conversation shell so members can keep chatting.
     * Delete-chat remains a separate for-me hide.
     */
    public function clearConversation(User $user, Conversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);

        $conversationId = (int) $conversation->id;
        $clearedAt = now();

        DB::transaction(function () use ($conversation, $conversationId, $clearedAt) {
            // Collect media before wipe so shared stickers/forwards stay alive.
            $mediaIds = Message::withTrashed()
                ->where('conversation_id', $conversationId)
                ->whereNotNull('media_id')
                ->pluck('media_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            // Pins / deletions / reactions cascade from messages FK.
            MessagePin::where('conversation_id', $conversationId)->delete();

            Message::withTrashed()
                ->where('conversation_id', $conversationId)
                ->forceDelete();

            $conversation->update([
                'last_message_id' => null,
                'last_message_at' => null,
            ]);

            // Safety watermark: any late flush of pre-clear rows is ignored.
            foreach ($conversation->users()->pluck('users.id') as $uid) {
                $conversation->users()->updateExistingPivot($uid, [
                    'cleared_at' => $clearedAt,
                ]);
            }

            foreach ($mediaIds as $mediaId) {
                $this->releaseMediaIfUnreferenced($mediaId);
            }
        });

        // Drop hot Redis rows + tombstone so outbox cannot resurrect them.
        $this->outbox->clearHotConversation($conversationId, $clearedAt);

        $clearedByName = trim("{$user->first_name} {$user->last_name}") ?: ($user->username ?: '');

        $this->notifyAllParticipants($conversation, 'conversation.cleared', [
            'conversation_id' => $conversationId,
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

    /**
     * Search message bodies across every conversation the user can see.
     */
    public function searchAllMessages(User $user, array $filters): LengthAwarePaginator
    {
        $q = Message::query()
            ->visibleTo($user)
            ->where('type', '!=', 'system')
            ->where(function ($w) {
                $w->where('is_encrypted', false)->orWhereNull('is_encrypted');
            });

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            if ($term !== '') {
                $q->where('body', 'like', "%{$term}%");
            }
        }

        if (! empty($filters['from'])) {
            $q->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $q->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        return $q->with([
            'user:id,first_name,last_name,username,profile_pic',
            'conversation' => function ($c) {
                $c->with(['users:id,first_name,last_name,username,profile_pic']);
            },
        ])
            ->orderByDesc('id')
            ->paginate(40);
    }

    public function getMessages(User $user, Conversation $conversation, ?int $beforeId = null): Paginator
    {
        $isMember = $conversation->hasParticipant($user);
        if (! $isMember) {
            if (! $conversation->isCommunity() || ! $conversation->is_public) {
                throw new \RuntimeException('Forbidden');
            }
            $query = Message::query()
                ->where('conversation_id', $conversation->id)
                ->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('type')->orWhere('type', '!=', 'system');
                })
                ->with([
                    'user:id,first_name,last_name,username,profile_pic,last_seen',
                    'user.messengerSettings',
                    'forwardedFromUser:id,first_name,last_name,username,profile_pic,last_seen',
                    'forwardedFromUser.messengerSettings',
                    'replyTo' => fn ($q) => $q->with([
                        'user:id,first_name,last_name,username,profile_pic,last_seen',
                    ]),
                    'reactions',
                ])
                ->orderByDesc('created_at')
                ->orderByDesc('id');
        } else {
            $query = $this->visibleMessagesQuery($user, $conversation);
        }

        if ($beforeId) {
            // Cursor pagination by id still works: older pages are lower ids when
            // the shared Redis/Postgres sequence is used. Also bound by time so
            // any legacy mis-ordered rows stay reachable.
            $pivot = Message::query()->whereKey($beforeId)->first(['id', 'created_at']);
            if ($pivot) {
                $query->where(function ($q) use ($pivot) {
                    $q->where('created_at', '<', $pivot->created_at)
                        ->orWhere(function ($q2) use ($pivot) {
                            $q2->where('created_at', '=', $pivot->created_at)
                                ->where('id', '<', $pivot->id);
                        });
                });
            } else {
                $query->where('id', '<', $beforeId);
            }
        }

        // A cursor-like "before_id" request does not need an expensive total
        // count. Fetch one extra row to derive has_more.
        $perPage = app(MessengerSystemConfig::class)->int('messages_per_page')
            ?: (int) config('messenger.messages_per_page', 50);
        $rows = $query->limit($perPage + 1)->get();

        // Merge not-yet-flushed Redis hot messages into the latest page so a
        // refresh never hides a message that already arrived over the live path.
        if ($isMember && ! $beforeId && $this->outbox->isActive()) {
            $hot = $this->rejectClearedHotRows(
                $user,
                $conversation,
                collect($this->outbox->hotMessagesForConversation($conversation->id))
                    ->map(fn (array $row) => $this->hydrateMessageRow($user, $row))
            );
            $byId = $rows->keyBy('id');
            foreach ($hot as $msg) {
                if (! $byId->has($msg->id)) {
                    $rows->push($msg);
                }
            }
        }

        $paginator = new Paginator(
            $rows,
            $perPage,
            Paginator::resolveCurrentPage(),
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );

        // Chronological for chat UI: created_at (precise), then id tiebreaker.
        $collection = $paginator->getCollection()
            ->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        // Derive effective receipts from peer last_read_at so write-behind
        // delays cannot demote already-read ticks after refresh / re-entry.
        if ($isMember) {
            $collection = $this->applyPeerReadReceipts($user, $conversation, $collection);
        }

        $paginator->setCollection($collection);

        return $paginator;
    }

    /**
     * When message.read_at is still null (outbox write-behind), expose an
     * effective read_at from any other participant's last_read_at cursor.
     * Never demotes an already-stamped read_at / delivered_at.
     *
     * @param  \Illuminate\Support\Collection<int, Message>  $messages
     * @param  mixed  $peerReadAt  Optional preloaded cursor (string|Carbon|null)
     * @return \Illuminate\Support\Collection<int, Message>
     */
    protected function applyPeerReadReceipts(User $viewer, Conversation $conversation, $messages, $peerReadAt = null)
    {
        if ($messages->isEmpty()) {
            return $messages;
        }

        if ($peerReadAt === null) {
            $peerReadAt = DB::table('conversation_user')
                ->where('conversation_id', $conversation->id)
                ->where('user_id', '!=', $viewer->id)
                ->whereNull('deleted_at')
                ->whereNotNull('last_read_at')
                ->max('last_read_at');
        }

        if (! $peerReadAt) {
            return $messages;
        }

        try {
            $peerReadCarbon = $peerReadAt instanceof Carbon
                ? $peerReadAt
                : Carbon::parse($peerReadAt);
        } catch (\Throwable) {
            return $messages;
        }

        return $messages->map(function (Message $message) use ($viewer, $peerReadCarbon) {
            if ((int) $message->user_id !== (int) $viewer->id) {
                return $message;
            }
            if ($message->read_at) {
                return $message;
            }
            $created = $message->created_at;
            if (! $created || $created->gt($peerReadCarbon)) {
                return $message;
            }
            $message->setAttribute('read_at', $peerReadCarbon);
            if (! $message->delivered_at) {
                $message->setAttribute('delivered_at', $peerReadCarbon);
            }

            return $message;
        });
    }

    /**
     * Shared media for a conversation (Telegram-style profile tabs).
     *
     * Filters: photo | video | gif | audio | voice | links | media (photo+video+gif)
     *
     * @return array{messages: \Illuminate\Support\Collection, has_more: bool}
     */
    public function getSharedMedia(
        User $user,
        Conversation $conversation,
        string $filter,
        ?int $beforeId = null,
        int $limit = 40
    ): array {
        $isMember = $conversation->hasParticipant($user);
        if (! $isMember) {
            if (! $conversation->isCommunity() || ! $conversation->is_public) {
                throw new \RuntimeException('Forbidden');
            }
            $query = Message::query()
                ->where('conversation_id', $conversation->id)
                ->whereNull('deleted_at');
        } else {
            $query = Message::query()
                ->visibleTo($user)
                ->where('conversation_id', $conversation->id);
        }

        $this->applySharedMediaFilter($query, $filter);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $limit = max(1, min(100, $limit));
        $rows = $query
            ->with(['user:id,first_name,last_name,username,profile_pic'])
            ->orderByDesc('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;

        return [
            'messages' => $rows->take($limit)->values(),
            'has_more' => $hasMore,
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Message>  $query
     */
    protected function applySharedMediaFilter($query, string $filter): void
    {
        switch ($filter) {
            case 'photo':
                // Stickers are stored as photo + meta.sticker — exclude from gallery/shared media.
                $query->where('type', Message::TYPE_PHOTO)
                    ->whereRaw("COALESCE((meta->>'sticker')::boolean, false) = false");
                break;
            case 'video':
                $query->where('type', Message::TYPE_VIDEO)
                    ->whereRaw("COALESCE((meta->>'animation')::boolean, false) = false")
                    ->whereRaw("COALESCE((meta->>'silent')::boolean, false) = false");
                break;
            case 'gif':
                $query->where('type', Message::TYPE_VIDEO)
                    ->where(function ($w) {
                        $w->whereRaw("COALESCE((meta->>'animation')::boolean, false) = true")
                            ->orWhereRaw("COALESCE((meta->>'silent')::boolean, false) = true");
                    });
                break;
            case 'media':
                $query->where(function ($w) {
                    $w->where(function ($photo) {
                        $photo->where('type', Message::TYPE_PHOTO)
                            ->whereRaw("COALESCE((meta->>'sticker')::boolean, false) = false");
                    })->orWhere('type', Message::TYPE_VIDEO);
                });
                break;
            case 'audio':
                $query->where('type', Message::TYPE_AUDIO);
                break;
            case 'voice':
                $query->where('type', Message::TYPE_VOICE);
                break;
            case 'file':
                $query->where('type', Message::TYPE_FILE);
                break;
            case 'links':
                $query->where(function ($w) {
                    $w->where('body', 'like', '%http://%')
                        ->orWhere('body', 'like', '%https://%')
                        ->orWhere('body', 'like', '%www.%')
                        ->orWhere('body', 'like', '%/messenger/join/%')
                        ->orWhere('body', 'like', '%/messenger/@%');
                })->where(function ($w) {
                    $w->whereNull('type')->orWhere('type', '!=', Message::TYPE_SYSTEM);
                });
                break;
            default:
                throw new \InvalidArgumentException('Invalid shared media filter');
        }
    }

    public function sendMessage(User $user, Conversation $conversation, string $body, ?string $clientId = null, array $options = []): Message
    {
        $this->assertParticipant($user, $conversation);
        $this->assertNotBlocked($user, $conversation);

        $isEncrypted = ! empty($options['is_encrypted']);
        $crypto = app(MessengerCryptoService::class);
        // Stickers are shared pack assets (Telegram-like) — not secret-chat ciphertext blobs.
        $metaOpt = is_array($options['meta'] ?? null) ? $options['meta'] : [];
        $isPublicSticker = ! empty($metaOpt['sticker']);
        if (
            ! $isEncrypted
            && $crypto->conversationShouldEncrypt($conversation)
            && ($options['type'] ?? 'text') !== 'system'
            && ! $isPublicSticker
        ) {
            // Clients must encrypt before send for E2E conversations.
            throw new \InvalidArgumentException('This conversation requires end-to-end encryption');
        }

        $plainForRules = $isEncrypted ? '' : $body;
        $this->groups()->assertCanSend($user, $conversation, $plainForRules);

        $msgType = $options['type'] ?? Message::TYPE_TEXT;
        $body = $isEncrypted ? (string) $body : trim($body);
        $isMedia = in_array($msgType, Message::MEDIA_TYPES, true);
        if ($body === '' && ! $isMedia) {
            throw new \InvalidArgumentException('Message body cannot be empty');
        }

        $maxLen = $isEncrypted
            ? (int) config('messenger.e2e.max_ciphertext_length', 65536)
            : (app(\App\Services\Messenger\MessengerSystemConfig::class)->int('max_message_length')
                ?: (int) config('messenger.max_message_length', 5000));
        if (mb_strlen($body) > $maxLen) {
            throw new \InvalidArgumentException("Message exceeds maximum length of {$maxLen} characters");
        }

        // Channels: replies are disabled
        if ($conversation->isChannel() && ! empty($options['reply_to_id'])) {
            throw new \InvalidArgumentException('Replies are disabled in channels');
        }

        // Idempotent send via client_id (Redis first; DB only on cold/durable path)
        if ($clientId) {
            if ($this->outbox->isActive()) {
                $hotId = $this->outbox->findMessageIdByClient($conversation->id, $user->id, $clientId);
                if ($hotId) {
                    $hot = $this->outbox->getHotMessage($hotId);
                    if ($hot) {
                        return $this->hydrateMessageRow($user, $hot);
                    }
                }
                // Hot path: skip Postgres client_id lookup — Redis is source of
                // truth for in-flight sends; durable flush will enforce uniqueness.
            } else {
                $existing = Message::where('conversation_id', $conversation->id)
                    ->where('user_id', $user->id)
                    ->where('client_id', $clientId)
                    ->first();

                if ($existing) {
                    return $this->visibleMessageOrFail($user, $existing->id)
                        ->load($this->messageRelations($user));
                }
            }
        }

        // A hidden, cleared, deleted, or cross-conversation reply target is
        // indistinguishable from an unknown ID. Prefer Redis hot row on hot path.
        $replyToId = $options['reply_to_id'] ?? null;
        if ($replyToId) {
            $replyOk = false;
            if ($this->outbox->isActive()) {
                $hotReply = $this->outbox->getHotMessage((int) $replyToId);
                if ($hotReply && (int) ($hotReply['conversation_id'] ?? 0) === (int) $conversation->id) {
                    $replyOk = true;
                }
            }
            if (! $replyOk) {
                $reply = Message::query()
                    ->visibleTo($user)
                    ->whereKey($replyToId)
                    ->where('conversation_id', $conversation->id)
                    ->first();
                $reply ?? throw (new ModelNotFoundException)->setModel(Message::class, [$replyToId]);
            }
        }

        $mentions = [];
        if (! $isEncrypted && $conversation->isCommunity()) {
            $mentions = $this->groups()->parseMentions($body);
        } elseif ($isEncrypted && ! empty($options['mention_ids']) && is_array($options['mention_ids'])) {
            // Opaque mention routing ids only — no plaintext body parsing.
            $mentions = array_values(array_unique(array_map('intval', $options['mention_ids'])));
        }

        $attributes = [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'client_id' => $clientId ?? Str::uuid()->toString(),
            'body' => $body,
            'type' => $msgType,
            'is_encrypted' => $isEncrypted,
            'sender_device_id' => $isEncrypted ? ($options['sender_device_id'] ?? null) : null,
            'e2e' => $isEncrypted ? ($options['e2e'] ?? null) : null,
            'reply_to_id' => $replyToId,
            'reply_show_title' => $options['reply_show_title'] ?? true,
            'forwarded_from_user_id' => $options['forwarded_from_user_id'] ?? null,
            'media_id' => $options['media_id'] ?? null,
            'is_silent' => (bool) ($options['is_silent'] ?? false),
            'scheduled_at' => $options['scheduled_at'] ?? null,
            'auto_delete_at' => $options['auto_delete_at'] ?? null,
            'mentions' => $mentions ?: null,
            'meta' => $options['meta'] ?? null,
        ];

        // Keep media_id on meta for older clients / debugging.
        if (! empty($attributes['media_id'])) {
            $meta = is_array($attributes['meta']) ? $attributes['meta'] : [];
            $meta['media_id'] = (int) $attributes['media_id'];
            $attributes['meta'] = $meta;
        }

        // Channel signatures: stamp the human poster on the post meta.
        $signable = array_merge(['text', 'location'], Message::MEDIA_TYPES);
        if ($conversation->isChannel() && $conversation->signatures_enabled && in_array($msgType, $signable, true)) {
            $meta = is_array($attributes['meta']) ? $attributes['meta'] : [];
            $meta['post_author'] = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
            ];
            $attributes['meta'] = $meta;
        }

        if ($isEncrypted && ! $conversation->is_encrypted) {
            $conversation->forceFill(['is_encrypted' => true])->save();
        }

        // Hot Redis path for text (including E2E ciphertext). Media/location stay
        // durable so file meta is written with the row. Outbox flush persists e2e.
        if ($this->outbox->isActive() && $msgType === 'text') {
            $message = $this->sendMessageHot($user, $conversation, $attributes);
        } else {
            $message = $this->sendMessageDurable($user, $conversation, $attributes);
        }

        // Community counters / slow-mode must not sit on the live delivery path.
        if ($conversation->isCommunity()) {
            $actorId = (int) $user->id;
            $conversationId = (int) $conversation->id;
            dispatch(function () use ($actorId, $conversationId) {
                $actor = User::query()->find($actorId);
                $conv = Conversation::query()->find($conversationId);
                if ($actor && $conv) {
                    $this->groups()->onMessageSent($actor, $conv);
                }
            })->afterResponse();
        }

        return $message;
    }

    /**
     * Upload a chat media file and create a photo/video/voice/audio/file message.
     */
    public function sendMediaMessage(
        User $user,
        Conversation $conversation,
        UploadedFile $file,
        string $type,
        string $caption = '',
        ?string $clientId = null,
        array $options = []
    ): Message {
        if (! in_array($type, Message::MEDIA_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid media type');
        }

        $meta = $this->storeMediaFile($file, $type, $options);
        $media = MessengerMedia::createFromStoredMeta($user->id, $type, $meta);
        $meta = array_merge($meta, [
            'media_id' => $media->id,
            'media_uuid' => $media->uuid,
        ]);

        $message = $this->sendMessage($user, $conversation, $caption, $clientId, array_merge($options, [
            'type' => $type,
            'media_id' => $media->id,
            'meta' => array_merge($options['meta'] ?? [], $meta),
        ]));

        $media->bumpRef(1);

        // Async: metadata, video thumbs, HLS (skip encrypted / dedup aliases).
        if (config('messenger.media.process_async', true)
            && empty($meta['encrypted'])
            && empty($meta['canonical_media_id'])
            && in_array($type, [Message::TYPE_VIDEO, Message::TYPE_AUDIO, Message::TYPE_VOICE], true)
        ) {
            $media->forceFill([
                'processing_status' => 'pending',
                'hls_status' => $type === Message::TYPE_VIDEO ? 'pending' : 'none',
            ])->saveQuietly();
            \App\Jobs\ProcessMessengerMedia::dispatch($media->id)->afterResponse();
        }

        return $message;
    }

    /**
     * Send a media message that reuses an existing messenger_media row (no re-upload).
     * Used for forwards and sticker sends that already live on static storage.
     */
    public function sendExistingMediaMessage(
        User $user,
        Conversation $conversation,
        MessengerMedia|int $media,
        string $caption = '',
        ?string $clientId = null,
        array $options = []
    ): Message {
        $media = $media instanceof MessengerMedia
            ? $media
            : MessengerMedia::query()->findOrFail((int) $media);

        $this->assertCanUseMedia($user, $media);

        $type = $options['type'] ?? $media->kind;
        if (! in_array($type, Message::MEDIA_TYPES, true)) {
            // Stickers are stored as kind=sticker but sent as photo messages.
            $type = Message::TYPE_PHOTO;
        }

        $meta = array_merge($media->toMessageMeta(), $options['meta'] ?? []);
        // Public stickers must expose a CDN-capable url for recipients even when
        // reusing an existing messenger_media row (pack send / forward).
        if (! empty($meta['sticker']) && empty($meta['encrypted'])) {
            $meta['private'] = false;
            if (empty($meta['url']) && ! empty($media->url)) {
                $meta['url'] = $media->url;
            }
            if (empty($meta['url']) && ! empty($media->path)) {
                $diskName = (string) ($media->disk ?: config('messenger.media.disk', 'static'));
                $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url", ''), '/');
                if ($baseUrl !== '') {
                    $meta['url'] = $baseUrl.'/'.ltrim((string) $media->path, '/');
                }
            }
        }
        $message = $this->sendMessage($user, $conversation, $caption, $clientId, array_merge($options, [
            'type' => $type,
            'media_id' => $media->id,
            'meta' => $meta,
        ]));

        $media->bumpRef(1);

        return $message;
    }

    /**
     * Persist an uploaded media file and build message meta.
     * Stickers are stored publicly (like pack assets) so recipients can load via CDN/proxy
     * without depending on photo auto-download. Other chat media stays private by default.
     *
     * @return array<string, mixed>
     */
    protected function storeMediaFile(UploadedFile $file, string $type, array $options = []): array
    {
        $cfg = config('messenger.media', []);
        $diskName = $cfg['disk'] ?? 'static';
        $encryptedUpload = ! empty($options['encrypted']);
        $isSticker = ! $encryptedUpload && ! empty($options['meta']['sticker']);

        // Stickers use the public stickers folder (same as StickerPackController).
        if ($isSticker) {
            $private = false;
            $folder = 'images/messenger/stickers/'.date('Y/m/d');
        } else {
            $private = (bool) ($cfg['private'] ?? true);
            $baseFolder = $private
                ? trim($cfg['private_folder'] ?? 'private/messenger', '/')
                : trim($cfg['folder'] ?? 'images/messenger/chats', '/');
            $folder = $baseFolder.'/'.$type.'/'.date('Y/m/d');
        }

        try {
            if ($encryptedUpload) {
                $binName = Str::uuid()->toString().'.bin';
                $stored = Storage::disk($diskName)->putFileAs($folder, $file, $binName);
                $path = $stored ?: null;
            } else {
                // Content-addressed deduplication: reuse an existing blob for identical files.
                $sha256 = @hash_file('sha256', $file->getRealPath()) ?: null;
                $size = (int) $file->getSize();
                if ($sha256 && config('messenger.media.dedupe', true)) {
                    $existing = MessengerMedia::findDedupCandidate($sha256, $size, $type);
                    if ($existing) {
                        // Stickers must stay on a public path — never alias onto
                        // private chat media (recipients would get a broken CDN).
                        if ($isSticker && $existing->is_private) {
                            $existing = null;
                        }
                    }
                    if ($existing) {
                        $root = $existing->storageRoot();
                        $meta = [
                            'disk' => $root->disk,
                            'path' => $root->path,
                            'thumb_path' => $root->thumb_path,
                            'cover_path' => $root->cover_path,
                            'hls_path' => $root->hls_path,
                            'hls_status' => $root->hls_status,
                            'processing_status' => $root->processing_status,
                            'variants' => $root->variants,
                            'metadata' => $root->metadata,
                            'canonical_media_id' => $root->id,
                            'private' => $private,
                            'encrypted' => false,
                            'mime' => $root->mime ?: ($file->getMimeType() ?: 'application/octet-stream'),
                            'size' => $root->size_bytes ?: $size,
                            'name' => mb_substr($file->getClientOriginalName() ?: 'media', 0, 255),
                            'ext' => strtolower($file->getClientOriginalExtension() ?: pathinfo($file->getClientOriginalName() ?: '', PATHINFO_EXTENSION)),
                            'sha256' => $sha256,
                            'etag' => $root->etag ?: $sha256,
                            'width' => $root->width,
                            'height' => $root->height,
                            'duration' => $root->duration,
                            'shared' => true,
                        ];
                        if (! $private) {
                            $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url", ''), '/');
                            $publicUrl = $existing->url
                                ?: ($root->url ?? null)
                                ?: ($baseUrl !== '' ? $baseUrl.'/'.ltrim((string) $root->path, '/') : null);
                            if ($publicUrl) {
                                $meta['url'] = $publicUrl;
                            }
                        }
                        if (isset($options['width']) && is_numeric($options['width'])) {
                            $meta['width'] = (int) $options['width'];
                        }
                        if (isset($options['height']) && is_numeric($options['height'])) {
                            $meta['height'] = (int) $options['height'];
                        }
                        if (isset($options['duration']) && is_numeric($options['duration'])) {
                            $meta['duration'] = round((float) $options['duration'], 1);
                        }
                        if (! empty($options['silent']) || ! empty($options['animation'])) {
                            $meta['silent'] = true;
                            $meta['animation'] = true;
                        }
                        if (! empty($options['album_id']) && is_string($options['album_id'])) {
                            $meta['album_id'] = mb_substr($options['album_id'], 0, 64);
                            if (isset($options['album_index']) && is_numeric($options['album_index'])) {
                                $meta['album_index'] = (int) $options['album_index'];
                            }
                            if (isset($options['album_count']) && is_numeric($options['album_count'])) {
                                $meta['album_count'] = (int) $options['album_count'];
                            }
                        }

                        return $meta;
                    }
                }
                $path = Storage::disk($diskName)->putFile($folder, $file);
            }
        } catch (\Throwable $e) {
            Log::error('messenger.media_store_failed', [
                'disk' => $diskName,
                'folder' => $folder,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                stripos($e->getMessage(), 'quota') !== false
                    ? 'Disk quota exceeded on media storage'
                    : 'Failed to store media file: '.$e->getMessage(),
                0,
                $e
            );
        }
        if (! $path) {
            throw new \RuntimeException('Failed to store media file');
        }

        $origName = $file->getClientOriginalName() ?: ('media.'.$file->getClientOriginalExtension());
        $ext = strtolower($file->getClientOriginalExtension() ?: pathinfo($origName, PATHINFO_EXTENSION));
        $mime = $encryptedUpload
            ? 'application/octet-stream'
            : ($file->getMimeType() ?: 'application/octet-stream');
        $size = (int) $file->getSize();
        $sha256 = $encryptedUpload
            ? null
            : (@hash_file('sha256', $file->getRealPath()) ?: null);

        // Private meta: path only. Proxy URL is constructed by the API resource.
        // Public stickers also keep a CDN url for direct <img> load on recipients.
        $meta = [
            'disk' => $diskName,
            'path' => $path,
            'private' => $private,
            'encrypted' => $encryptedUpload,
            'mime' => $mime,
            'size' => $size,
            // Cleartext name/ext only for non-E2E; E2E puts them inside ciphertext.
            'name' => $encryptedUpload ? null : mb_substr($origName, 0, 255),
            'ext' => $encryptedUpload ? 'bin' : $ext,
            'sha256' => $sha256,
            'etag' => $sha256,
            'hls_status' => 'none',
            'processing_status' => 'ready',
        ];

        if (! $private && ! $encryptedUpload) {
            $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url", ''), '/');
            if ($baseUrl !== '') {
                $meta['url'] = $baseUrl.'/'.ltrim($path, '/');
            }
        }

        if (isset($options['width']) && is_numeric($options['width'])) {
            $meta['width'] = (int) $options['width'];
        }
        if (isset($options['height']) && is_numeric($options['height'])) {
            $meta['height'] = (int) $options['height'];
        }
        if (isset($options['duration']) && is_numeric($options['duration'])) {
            $meta['duration'] = round((float) $options['duration'], 1);
        }

        if (! empty($options['silent']) || ! empty($options['animation'])) {
            $meta['silent'] = true;
            $meta['animation'] = true;
        }

        if (! empty($options['album_id']) && is_string($options['album_id'])) {
            $meta['album_id'] = mb_substr($options['album_id'], 0, 64);
            if (isset($options['album_index']) && is_numeric($options['album_index'])) {
                $meta['album_index'] = (int) $options['album_index'];
            }
            if (isset($options['album_count']) && is_numeric($options['album_count'])) {
                $meta['album_count'] = (int) $options['album_count'];
            }
        }

        // Thumbnails only for non-encrypted photos (ciphertext cannot be imaged).
        if (! $encryptedUpload && $type === Message::TYPE_PHOTO) {
            $dims = $this->readImageDimensions($file);
            if ($dims) {
                $meta['width'] = $meta['width'] ?? $dims[0];
                $meta['height'] = $meta['height'] ?? $dims[1];
            }
            $thumbPath = $this->storePhotoThumbPrivate($file, $folder, $diskName);
            if ($thumbPath) {
                $meta['thumb_path'] = $thumbPath;
            }
        }

        if (! $encryptedUpload && $type === Message::TYPE_AUDIO) {
            $cover = $options['cover'] ?? null;
            if ($cover instanceof UploadedFile && $cover->isValid()) {
                $coverPath = $this->storeAudioCoverPrivate($cover, $folder, $diskName);
                if ($coverPath) {
                    $meta['cover_path'] = $coverPath;
                }
            }
        }

        // Ensure private deny marker exists on the static disk (Apache .htaccess).
        if ($private) {
            $this->ensurePrivateDenyMarker(
                $diskName,
                $baseFolder ?? trim((string) ($cfg['private_folder'] ?? 'private/messenger'), '/')
            );
        }

        return $meta;
    }

    protected function storeAudioCoverPrivate(UploadedFile $cover, string $folder, string $diskName): ?string
    {
        try {
            $path = Storage::disk($diskName)->putFile($folder.'/covers', $cover);

            return $path ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Tiny low-quality JPEG used as the pre-download (blurred) preview.
     * Stored privately (path only, no public URL).
     */
    protected function storePhotoThumbPrivate(UploadedFile $file, string $folder, string $diskName): ?string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return null;
        }

        try {
            $binary = @file_get_contents($file->getRealPath());
            if ($binary === false) {
                return null;
            }
            $src = @imagecreatefromstring($binary);
            if (! $src) {
                return null;
            }

            $sw = imagesx($src);
            $sh = imagesy($src);
            if ($sw < 1 || $sh < 1) {
                imagedestroy($src);

                return null;
            }

            $maxEdge = (int) (config('messenger.media.thumb_max_edge') ?: 48);
            $scale = min(1, $maxEdge / max($sw, $sh));
            $tw = max(1, (int) round($sw * $scale));
            $th = max(1, (int) round($sh * $scale));

            $dst = imagecreatetruecolor($tw, $th);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $sw, $sh);

            ob_start();
            $quality = (int) (config('messenger.media.thumb_quality') ?: 45);
            imagejpeg($dst, null, max(20, min(80, $quality)));
            $jpeg = ob_get_clean();

            imagedestroy($src);
            imagedestroy($dst);

            if ($jpeg === false || $jpeg === '') {
                return null;
            }

            $thumbPath = $folder.'/'.Str::uuid()->toString().'_thumb.jpg';
            $ok = Storage::disk($diskName)->put($thumbPath, $jpeg);
            if (! $ok) {
                return null;
            }

            return $thumbPath;
        } catch (\Throwable $e) {
            Log::warning('messenger.thumb_failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Best-effort: drop an Apache deny-all .htaccess under private/ on the static disk.
     */
    protected function ensurePrivateDenyMarker(string $diskName, string $privateRoot): void
    {
        static $done = [];
        $key = $diskName.':'.$privateRoot;
        if (isset($done[$key])) {
            return;
        }
        $done[$key] = true;

        try {
            $disk = Storage::disk($diskName);
            $htaccess = trim($privateRoot, '/').'/.htaccess';
            if (! $disk->exists($htaccess)) {
                $disk->put($htaccess, "Require all denied\nDeny from all\n");
            }
            // Also place nginx snippet note for operators (non-executable).
            $note = trim($privateRoot, '/').'/README.PRIVATE.txt';
            if (! $disk->exists($note)) {
                $disk->put($note, "This folder must not be publicly web-accessible.\n"
                    ."Nginx: location ^~ /private/ { deny all; return 403; }\n"
                    ."Apache: .htaccess Deny from all (auto-created).\n"
                    ."Clients must use authenticated GET /api/messenger/media/{id}.\n");
            }
        } catch (\Throwable $e) {
            // Non-fatal — deploy config is the real enforcement.
        }
    }

    /**
     * @deprecated Kept for wallpaper uploads that still need a public URL temporarily.
     */
    protected function storeAudioCover(UploadedFile $cover, string $folder, string $diskName, string $baseUrl): ?string
    {
        try {
            $path = Storage::disk($diskName)->putFile($folder.'/covers', $cover);
            if (! $path) {
                return null;
            }

            return $baseUrl.'/'.ltrim($path, '/');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array{0:int,1:int}|null
     */
    protected function readImageDimensions(UploadedFile $file): ?array
    {
        try {
            $info = @getimagesize($file->getRealPath());
            if ($info && ! empty($info[0]) && ! empty($info[1])) {
                return [(int) $info[0], (int) $info[1]];
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return null;
    }

    /**
     * @deprecated Use storePhotoThumbPrivate for chat media.
     */
    protected function storePhotoThumb(UploadedFile $file, string $folder, string $diskName, string $baseUrl): ?string
    {
        $path = $this->storePhotoThumbPrivate($file, $folder, $diskName);
        if (! $path) {
            return null;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    /**
     * Internal system event message for groups/channels (not user-authored text).
     */
    public function sendSystemMessage(Conversation $conversation, User $actor, string $body, string $event): Message
    {
        $attributes = [
            'conversation_id' => $conversation->id,
            'user_id' => $actor->id,
            'client_id' => 'sys-'.Str::uuid()->toString(),
            'body' => $body,
            'type' => 'system',
            'reply_to_id' => null,
            'reply_show_title' => false,
            'forwarded_from_user_id' => null,
            'meta' => ['event' => $event],
        ];

        return $this->sendMessageDurable($actor, $conversation, $attributes);
    }

    /**
     * Redis-first send: allocate id in RAM, fan-out over Reverb immediately,
     * enqueue durable writes for the outbox flusher.
     */
    protected function sendMessageHot(User $user, Conversation $conversation, array $attributes): Message
    {
        $now = now();
        $id = $this->outbox->allocateMessageId();
        // Microsecond-precision string keeps rapid sends strictly ordered even
        // when several land in the same whole second.
        $stamp = $now->format('Y-m-d H:i:s.u');
        $row = array_merge($attributes, [
            'id' => $id,
            'read_at' => null,
            'delivered_at' => null,
            'edited_at' => null,
            'deleted_at' => null,
            'created_at' => $stamp,
            'updated_at' => $stamp,
        ]);

        $message = $this->hydrateMessageRow($user, $row);

        $this->outbox->enqueue([
            'op' => 'message.create',
            'message' => $row,
            'conversation_id' => $conversation->id,
            'restore_deleted' => true,
        ]);
        $this->outbox->cacheHotMessage($row);
        $this->outbox->rememberClientId(
            $conversation->id,
            $user->id,
            (string) $attributes['client_id'],
            $id
        );

        // Live fan-out + durable event enqueue (broadcast uses ephemeral id=0).
        $this->notifyMessageToAllParticipants($conversation, $message, 'message.new');

        $this->maybeFlushOutboxAfterResponse();

        return $message;
    }

    /**
     * Durable DB-first send (media / location / Redis-off fallback).
     * When the hot-path Redis sequence is active, allocate the same id space as
     * text so photo→text never collides or reorders after refresh.
     */
    protected function sendMessageDurable(User $user, Conversation $conversation, array $attributes): Message
    {
        $now = now();
        $stamp = $now->format('Y-m-d H:i:s.u');
        $allocatedId = null;

        if ($this->outbox->isActive()) {
            $allocatedId = $this->outbox->allocateMessageId();
            $attributes['id'] = $allocatedId;
        }

        // Always stamp created_at so rapid media+text share a precise clock order.
        $attributes['created_at'] = $attributes['created_at'] ?? $stamp;
        $attributes['updated_at'] = $attributes['updated_at'] ?? $stamp;

        $message = DB::transaction(function () use ($attributes, $conversation, $now) {
            $message = Message::create($attributes);

            $conversation->update([
                'last_message_id' => $message->id,
                'last_message_at' => $now,
            ]);

            // Restore conversation for all participants who had deleted it
            DB::table('conversation_user')
                ->where('conversation_id', $conversation->id)
                ->whereNotNull('deleted_at')
                ->update(['deleted_at' => null, 'updated_at' => now()]);

            return $message;
        });

        // Keep Redis seq ahead of any autoincrement path (Redis off mid-flight, etc.).
        $this->outbox->ensureSequenceAtLeast((int) $message->id);

        // Explicit Redis ids leave the Postgres serial behind — bump it so a
        // later Redis-off durable create cannot collide.
        if ($allocatedId) {
            try {
                DB::statement(
                    "SELECT setval(pg_get_serial_sequence('messages', 'id'), (SELECT COALESCE(MAX(id), 1) FROM messages))"
                );
            } catch (\Throwable $e) {
                // Non-Postgres or missing sequence — ignore.
            }
        }

        if ($allocatedId && ! empty($attributes['client_id'])) {
            $this->outbox->rememberClientId(
                $conversation->id,
                $user->id,
                (string) $attributes['client_id'],
                (int) $message->id
            );
        }

        $message->load($this->messageRelations($user));

        // Notify outside the write transaction so live delivery is not blocked
        // by messenger_events inserts.
        $this->notifyMessageToAllParticipants($conversation, $message, 'message.new');

        return $message;
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
        // Fail before inspecting source IDs so target blocking cannot be used
        // as a side channel for message visibility.
        $this->assertNotBlocked($user, $target);

        $uniqueIds = collect($messageIds)->map(fn ($id) => (int) $id)->unique()->values();
        $sources = Message::query()
            ->visibleTo($user)
            ->whereIn('id', $uniqueIds)
            ->with(['conversation', 'media'])
            ->get()
            ->keyBy('id');

        if ($sources->count() !== $uniqueIds->count()) {
            Log::warning('messenger.forward.invisible_source', [
                'user_id' => $user->id,
                'target_id' => $target->id,
                'target_type' => $target->type,
                'requested' => $uniqueIds->all(),
                'found' => $sources->keys()->all(),
            ]);
            throw (new ModelNotFoundException)->setModel(Message::class, $uniqueIds->all());
        }

        return DB::transaction(function () use ($user, $messageIds, $target, $dropAuthor, $sources) {
            $created = [];
            $crypto = app(MessengerCryptoService::class);
            foreach ($messageIds as $messageId) {
                $source = $sources->get((int) $messageId);

                if (! empty($source->is_encrypted) || ! empty(($source->meta['encrypted'] ?? false))) {
                    Log::info('messenger.forward.encrypted_source_requires_client', [
                        'user_id' => $user->id,
                        'target_id' => $target->id,
                        'target_type' => $target->type,
                        'source_id' => $source->id,
                    ]);
                    throw new \InvalidArgumentException(
                        'Encrypted messages must be re-encrypted on the client before forwarding'
                    );
                }

                // When $dropAuthor is true we forward "without quote": the
                // message is re-sent as if authored by the forwarder.
                if ($dropAuthor) {
                    $options = [];
                } else {
                    $options = [
                        'forwarded_from_user_id' => $source->forwarded_from_user_id ?: $source->user_id,
                        'meta' => $this->buildForwardMeta($source),
                    ];
                }

                $sourceType = $source->type ?: 'text';
                if ($sourceType !== 'text') {
                    $options['type'] = $sourceType;
                }
                if ($sourceType === 'location') {
                    $srcMeta = is_array($source->meta) ? $source->meta : [];
                    $locMeta = [
                        'lat' => $srcMeta['lat'] ?? null,
                        'lng' => $srcMeta['lng'] ?? null,
                        'accuracy' => $srcMeta['accuracy'] ?? null,
                    ];
                    $options['meta'] = array_merge($options['meta'] ?? [], $locMeta);
                }

                // Server-side forward only works into non-E2E targets (Saved Messages,
                // public channels). Private/group E2E targets must use the client path.
                if ($crypto->conversationShouldEncrypt($target)) {
                    Log::info('messenger.forward.encrypted_target_requires_client', [
                        'user_id' => $user->id,
                        'target_id' => $target->id,
                        'target_type' => $target->type,
                        'source_id' => $source->id,
                    ]);
                    throw new \InvalidArgumentException(
                        'Forwarding into encrypted chats must be done client-side'
                    );
                }

                // Media forwards reuse messenger_media by id (no re-upload).
                if (in_array($sourceType, Message::MEDIA_TYPES, true)) {
                    $media = $this->resolveOrCreateMediaFromMessage($source);
                    if (! $media) {
                        Log::warning('messenger.forward.missing_media_path', [
                            'user_id' => $user->id,
                            'target_id' => $target->id,
                            'source_id' => $source->id,
                        ]);
                        throw new \InvalidArgumentException('Source media has no storage path');
                    }
                    $srcMeta = is_array($source->meta) ? $source->meta : [];
                    $extraMeta = array_filter([
                        'silent' => $srcMeta['silent'] ?? null,
                        'animation' => $srcMeta['animation'] ?? null,
                        'sticker' => $srcMeta['sticker'] ?? null,
                        'sticker_id' => $srcMeta['sticker_id'] ?? null,
                        'sticker_pack_id' => $srcMeta['sticker_pack_id'] ?? null,
                        'sticker_emoji' => $srcMeta['sticker_emoji'] ?? null,
                        'sticker_kind' => $srcMeta['sticker_kind'] ?? null,
                        'album_id' => $srcMeta['album_id'] ?? null,
                        'album_index' => $srcMeta['album_index'] ?? null,
                        'album_count' => $srcMeta['album_count'] ?? null,
                    ], fn ($v) => $v !== null && $v !== '');
                    if (! $dropAuthor) {
                        $extraMeta = array_merge($options['meta'] ?? [], $extraMeta);
                    }
                    $created[] = $this->sendExistingMediaMessage(
                        $user,
                        $target,
                        $media,
                        $source->body ?? '',
                        null,
                        array_merge($options, [
                            'type' => $sourceType,
                            'meta' => $extraMeta,
                        ])
                    );

                    continue;
                }

                $created[] = $this->sendMessage($user, $target, $source->body ?? '', null, $options);
            }

            Log::info('messenger.forward.ok', [
                'user_id' => $user->id,
                'target_id' => $target->id,
                'target_type' => $target->type,
                'count' => count($created),
                'source_ids' => array_values(array_map('intval', $messageIds)),
            ]);

            return $created;
        });
    }

    /**
     * Create a message that reuses another message's media storage paths
     * (no re-upload). Used by client-side E2E forwards: same ciphertext blob,
     * new conversation-key envelope. Source must be visible to the forwarder.
     */
    public function sendReferencedForward(
        User $user,
        Conversation $target,
        int $sourceMessageId,
        string $body = '',
        ?string $clientId = null,
        array $options = []
    ): Message {
        $this->assertParticipant($user, $target);
        $this->assertNotBlocked($user, $target);

        $source = Message::query()
            ->visibleTo($user)
            ->whereKey($sourceMessageId)
            ->with('conversation')
            ->firstOrFail();

        $sourceType = $source->type ?: 'text';
        if (! in_array($sourceType, Message::MEDIA_TYPES, true)) {
            throw new \InvalidArgumentException('Only media messages can be forwarded by reference');
        }

        $media = $this->resolveOrCreateMediaFromMessage($source);
        $srcMeta = is_array($source->meta) ? $source->meta : [];
        if (! $media && empty($srcMeta['path']) && empty($srcMeta['url'])) {
            throw new \InvalidArgumentException('Source media has no storage path');
        }

        $srcEncrypted = ! empty($source->is_encrypted) || ! empty($srcMeta['encrypted']);
        $dstEncrypted = ! empty($options['is_encrypted']);

        // Ciphertext and plaintext blobs are not interchangeable.
        if ($srcEncrypted !== $dstEncrypted) {
            throw new \InvalidArgumentException(
                'Referenced media forward requires matching encryption mode on source and target'
            );
        }

        $mediaMeta = $media
            ? $media->toMessageMeta()
            : array_filter([
                'disk' => $srcMeta['disk'] ?? null,
                'path' => $srcMeta['path'] ?? null,
                'thumb_path' => $srcMeta['thumb_path'] ?? null,
                'cover_path' => $srcMeta['cover_path'] ?? null,
                'private' => $srcMeta['private'] ?? true,
                'url' => $srcMeta['url'] ?? null,
                'mime' => $srcMeta['mime'] ?? null,
                'size' => $srcMeta['size'] ?? null,
                'name' => $srcMeta['name'] ?? null,
                'ext' => $srcMeta['ext'] ?? null,
                'width' => $srcMeta['width'] ?? null,
                'height' => $srcMeta['height'] ?? null,
                'duration' => $srcMeta['duration'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

        if ($dstEncrypted) {
            $mediaMeta['encrypted'] = true;
            $mediaMeta['mime'] = 'application/octet-stream';
            $mediaMeta['name'] = null;
            $mediaMeta['ext'] = 'bin';
        }

        $mediaMeta = array_merge($mediaMeta, array_filter([
            'silent' => $srcMeta['silent'] ?? null,
            'animation' => $srcMeta['animation'] ?? null,
            'sticker' => $srcMeta['sticker'] ?? null,
            'sticker_id' => $srcMeta['sticker_id'] ?? null,
            'sticker_pack_id' => $srcMeta['sticker_pack_id'] ?? null,
            'sticker_emoji' => $srcMeta['sticker_emoji'] ?? null,
            'sticker_kind' => $srcMeta['sticker_kind'] ?? null,
            'album_id' => $srcMeta['album_id'] ?? null,
            'album_index' => $srcMeta['album_index'] ?? null,
            'album_count' => $srcMeta['album_count'] ?? null,
            // Prefer source message dimensions/duration when the media row is sparse.
            'width' => $srcMeta['width'] ?? ($mediaMeta['width'] ?? null),
            'height' => $srcMeta['height'] ?? ($mediaMeta['height'] ?? null),
            'duration' => $srcMeta['duration'] ?? ($mediaMeta['duration'] ?? null),
        ], fn ($v) => $v !== null && $v !== ''));

        // Clear attribution fields from the client (fwd_chat) + media paths.
        $clientMeta = is_array($options['meta'] ?? null) ? $options['meta'] : [];
        $fwdMeta = array_intersect_key($clientMeta, array_flip(['fwd_chat', 'fwd_message_id', 'post_author']));
        if (empty($options['drop_author'])) {
            if (empty($fwdMeta)) {
                $fwdMeta = $this->buildForwardMeta($source);
            }
            if (empty($options['forwarded_from_user_id'])) {
                $options['forwarded_from_user_id'] = $source->forwarded_from_user_id ?: $source->user_id;
            }
        } else {
            $options['forwarded_from_user_id'] = null;
            $fwdMeta = [];
        }

        $merged = array_merge($fwdMeta, $mediaMeta);

        if ($media) {
            return $this->sendExistingMediaMessage($user, $target, $media, $body, $clientId, array_merge($options, [
                'type' => $sourceType,
                'meta' => $merged,
                'is_encrypted' => $dstEncrypted,
                'encrypted' => $dstEncrypted,
            ]));
        }

        return $this->sendMessage($user, $target, $body, $clientId, array_merge($options, [
            'type' => $sourceType,
            'meta' => $merged,
            'is_encrypted' => $dstEncrypted,
            'encrypted' => $dstEncrypted,
        ]));
    }

    /**
     * Resolve messenger_media for a message, backfilling a row from legacy meta when needed.
     */
    public function resolveOrCreateMediaFromMessage(Message $message): ?MessengerMedia
    {
        if ($message->media_id) {
            $media = $message->relationLoaded('media')
                ? $message->media
                : MessengerMedia::query()->find($message->media_id);
            if ($media) {
                return $media;
            }
        }

        $meta = is_array($message->meta) ? $message->meta : [];
        if (! empty($meta['media_id'])) {
            $media = MessengerMedia::query()->find((int) $meta['media_id']);
            if ($media) {
                if (! $message->media_id) {
                    $message->forceFill(['media_id' => $media->id])->saveQuietly();
                }

                return $media;
            }
        }

        $path = $meta['path'] ?? null;
        $url = $meta['url'] ?? null;
        if ((! is_string($path) || $path === '') && (! is_string($url) || $url === '')) {
            return null;
        }

        if (is_string($path) && $path !== '') {
            $existing = MessengerMedia::query()->where('path', $path)->first();
            if ($existing) {
                if ((int) $message->media_id !== (int) $existing->id) {
                    $meta['media_id'] = $existing->id;
                    $meta['media_uuid'] = $existing->uuid;
                    $message->forceFill([
                        'media_id' => $existing->id,
                        'meta' => $meta,
                    ])->saveQuietly();
                }

                return $existing;
            }
        }

        $kind = in_array($message->type, Message::MEDIA_TYPES, true)
            ? $message->type
            : (! empty($meta['sticker']) ? 'sticker' : 'file');

        $media = MessengerMedia::createFromStoredMeta($message->user_id, $kind, $meta);
        $meta['media_id'] = $media->id;
        $meta['media_uuid'] = $media->uuid;
        $message->forceFill([
            'media_id' => $media->id,
            'meta' => $meta,
        ])->saveQuietly();
        $media->bumpRef(1);

        return $media;
    }

    /**
     * Caller may reuse a media asset only if they own it, can see a message that
     * references it, or it belongs to an accessible sticker pack.
     */
    protected function assertCanUseMedia(User $user, MessengerMedia $media): void
    {
        if ((int) $media->user_id === (int) $user->id) {
            return;
        }

        $viaMessage = Message::query()
            ->visibleTo($user)
            ->where('media_id', $media->id)
            ->exists();
        if ($viaMessage) {
            return;
        }

        $viaSticker = \App\Models\MessengerSticker::query()
            ->where('media_id', $media->id)
            ->whereHas('pack', function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('is_public', true)
                    ->orWhereIn('id', \App\Models\MessengerUserStickerPack::query()
                        ->where('user_id', $user->id)
                        ->select('pack_id'));
            })
            ->exists();
        if ($viaSticker) {
            return;
        }

        throw new \RuntimeException('Forbidden media');
    }

    /**
     * Preserve channel/group origin for Telegram-style "Forwarded from …" headers.
     */
    protected function buildForwardMeta(Message $source): array
    {
        $meta = is_array($source->meta) ? $source->meta : [];

        // Keep the earliest chat origin when re-forwarding.
        if (! empty($meta['fwd_chat']) && is_array($meta['fwd_chat'])) {
            return [
                'fwd_chat' => $meta['fwd_chat'],
                'fwd_message_id' => $meta['fwd_message_id'] ?? $source->id,
                'post_author' => $meta['post_author'] ?? null,
            ];
        }

        $chat = $source->conversation;
        if ($chat && $chat->isCommunity()) {
            return [
                'fwd_chat' => [
                    'id' => $chat->id,
                    'type' => $chat->type,
                    'title' => $chat->title,
                    'username' => $chat->username,
                    'avatar' => $chat->avatar,
                ],
                'fwd_message_id' => $source->id,
                'post_author' => $meta['post_author'] ?? null,
            ];
        }

        return array_filter([
            'post_author' => $meta['post_author'] ?? null,
        ]);
    }

    public function editMessage(User $user, Message $message, string $body, array $options = []): Message
    {
        $message = $this->visibleMessageOrFail($user, $message->id);
        $conversation = $message->conversation;

        if ($message->isSystem()) {
            throw new \RuntimeException('System messages cannot be edited');
        }

        if (! $message->isOwnedBy($user)) {
            throw new \RuntimeException('You can only edit your own messages');
        }

        $this->groups()->assertCanEdit($user, $conversation, $message);

        $isEncrypted = ! empty($options['is_encrypted']) || ! empty($message->is_encrypted);
        if ($isEncrypted && empty($options['is_encrypted'])) {
            throw new \InvalidArgumentException('Encrypted messages must be edited with ciphertext');
        }

        $body = $isEncrypted ? (string) $body : trim($body);
        if ($body === '' && ! $message->isMedia()) {
            throw new \InvalidArgumentException('Message body cannot be empty');
        }

        $mentions = null;
        if (! $isEncrypted && $conversation->isCommunity()) {
            $mentions = $this->groups()->parseMentions($body);
        }

        $update = [
            'body' => $body,
            'edited_at' => now(),
            'mentions' => $mentions,
        ];
        if ($isEncrypted) {
            $update['is_encrypted'] = true;
            if (! empty($options['e2e'])) {
                $update['e2e'] = $options['e2e'];
            }
            if (! empty($options['sender_device_id'])) {
                $update['sender_device_id'] = $options['sender_device_id'];
            }
        }

        $message->update($update);

        $message->load($this->messageRelations($user));

        // Notify everyone, including the editor's other devices.
        $this->notifyMessageToAllParticipants($message->conversation, $message, 'message.updated');

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
        $message = $this->visibleMessageOrFail($user, $message->id);
        $conversation = $message->conversation;
        $this->assertParticipant($user, $conversation);

        $conversationId = $message->conversation_id;
        $messageId = $message->id;

        if ($scope === 'me') {
            // Hide only from this user; sync to their own devices.
            $message->deletedForUsers()->syncWithoutDetaching([$user->id]);

            // Drop it from this user's pinned list too.
            MessagePin::where('message_id', $messageId)
                ->where('user_id', $user->id)
                ->delete();

            $this->emitEvent($user->id, $conversationId, 'message.deleted', [
                'message_id' => $messageId,
                'conversation_id' => $conversationId,
                'scope' => 'me',
            ]);

            return;
        }

        // scope === everyone
        if ($conversation->isCommunity()) {
            $this->groups()->assertCanDelete($user, $conversation, $message, 'everyone');
        } elseif (! $message->isOwnedBy($user)) {
            throw new \RuntimeException('You can only delete your own messages for everyone');
        }

        DB::transaction(function () use ($message, $messageId, $conversation) {
            // Pins reference this message; clear them for all participants.
            MessagePin::where('message_id', $messageId)->delete();

            // Capture media identity before soft-delete. Shared assets (forwards /
            // stickers) must stay on disk until no live references remain.
            $mediaId = $message->media_id
                ?: (is_array($message->meta) ? ($message->meta['media_id'] ?? null) : null);
            $mediaMeta = is_array($message->meta) ? $message->meta : null;
            $mediaType = (string) ($message->type ?: 'text');

            $message->delete();

            if ($mediaId && in_array($mediaType, Message::MEDIA_TYPES, true)) {
                $media = MessengerMedia::query()->find((int) $mediaId);
                if ($media) {
                    $media->dropRef(1);
                    $this->releaseMediaIfUnreferenced((int) $mediaId, $mediaType);
                }
            } elseif ($mediaMeta && in_array($mediaType, Message::MEDIA_TYPES, true)) {
                $path = $mediaMeta['path'] ?? null;
                $stillShared = false;
                if (is_string($path) && $path !== '') {
                    $stillShared = Message::query()
                        ->where('id', '!=', $messageId)
                        ->where(function ($q) use ($path) {
                            $q->where('meta->path', $path)
                                ->orWhereHas('media', fn ($m) => $m->where('path', $path));
                        })
                        ->exists();
                }
                if (! $stillShared) {
                    DB::afterCommit(function () use ($mediaMeta, $mediaType) {
                        DeleteMessengerMedia::dispatch($mediaMeta, $mediaType);
                    });
                }
            }
        });

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
     * @return int Number of messages actually deleted
     */
    public function bulkDeleteMessages(User $user, array $messageIds, string $scope = 'everyone'): int
    {
        $uniqueIds = collect($messageIds)->map(fn ($id) => (int) $id)->unique()->values();
        $query = Message::query()
            ->visibleTo($user)
            ->whereIn('id', $uniqueIds);

        $messages = $query->get();
        if ($messages->count() !== $uniqueIds->count()) {
            throw (new ModelNotFoundException)->setModel(Message::class, $uniqueIds->all());
        }
        if ($scope !== 'me' && $messages->contains(fn (Message $message) => ! $message->isOwnedBy($user))) {
            // In communities, admins may delete others' messages — defer to per-message checks.
            $nonOwned = $messages->filter(fn (Message $m) => ! $m->isOwnedBy($user));
            foreach ($nonOwned as $message) {
                $conversation = $message->conversation;
                if (! $conversation->isCommunity()) {
                    throw new \RuntimeException('You can only delete your own messages for everyone');
                }
                $this->groups()->assertCanDelete($user, $conversation, $message, 'everyone');
            }
        }

        return DB::transaction(function () use ($user, $messages, $scope) {
            $count = 0;
            foreach ($messages as $message) {
                $this->deleteMessage($user, $message, $scope);
                $count++;
            }

            return $count;
        });
    }

    public function markRead(User $user, Conversation $conversation): int
    {
        $this->assertParticipant($user, $conversation);

        // Always stamp pivot immediately (cheap). Unread APIs key off last_read_at
        // so badges clear before the write-behind message.read_at flush finishes.
        $readAt = now();

        $latestIncomingId = (int) (Message::query()
            ->visibleTo($user)
            ->where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->max('id') ?? 0);

        if ($this->outbox->isActive()) {
            foreach ($this->outbox->hotMessagesForConversation($conversation->id) as $row) {
                if ((int) ($row['user_id'] ?? 0) === (int) $user->id) {
                    continue;
                }
                $hid = (int) ($row['id'] ?? 0);
                if ($hid > $latestIncomingId) {
                    $latestIncomingId = $hid;
                }
            }
        }

        $conversation->users()->updateExistingPivot($user->id, [
            'last_read_at' => $readAt,
            'last_read_message_id' => $latestIncomingId > 0 ? $latestIncomingId : null,
        ]);

        if ($this->outbox->isActive()) {
            // Unique unread message ids from DB + hot cache (no double-count).
            $unreadIds = Message::query()
                ->visibleTo($user)
                ->where('conversation_id', $conversation->id)
                ->where('user_id', '!=', $user->id)
                ->whereNull('read_at')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $seen = array_fill_keys($unreadIds, true);

            foreach ($this->outbox->hotMessagesForConversation($conversation->id) as $row) {
                if ((int) ($row['user_id'] ?? 0) === (int) $user->id) {
                    continue;
                }
                if (! empty($row['read_at'])) {
                    continue;
                }
                $hid = (int) ($row['id'] ?? 0);
                if ($hid <= 0 || isset($seen[$hid])) {
                    continue;
                }
                $seen[$hid] = true;
                $unreadIds[] = $hid;
            }

            $updated = count($unreadIds);

            if ($updated > 0) {
                $readAtStr = $readAt->toDateTimeString();
                $this->outbox->enqueue([
                    'op' => 'messages.read',
                    'conversation_id' => $conversation->id,
                    'reader_id' => $user->id,
                    'read_at' => $readAtStr,
                    'message_ids' => $unreadIds,
                ]);

                // Stamp hot-cache rows so a subsequent merge does not resurrect unread.
                foreach ($this->outbox->hotMessagesForConversation($conversation->id) as $row) {
                    if ((int) $row['user_id'] === (int) $user->id) {
                        continue;
                    }
                    $row['read_at'] = $readAtStr;
                    $this->outbox->cacheHotMessage($row);
                }

                $this->notifyAllParticipants(
                    $conversation,
                    'messages.read',
                    [
                        'conversation_id' => $conversation->id,
                        'reader_id' => $user->id,
                        'count' => $updated,
                        'message_ids' => $unreadIds,
                        'read_at' => $readAt->toIso8601String(),
                    ]
                );

                $this->maybeFlushOutboxAfterResponse();
            }

            return $updated;
        }

        $unreadIds = Message::query()
            ->visibleTo($user)
            ->where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $updated = Message::query()
            ->visibleTo($user)
            ->where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => $readAt]);

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
                    'message_ids' => $unreadIds,
                    'read_at' => $readAt->toIso8601String(),
                ]
            );
        }

        return $updated;
    }

    /**
     * Recipient device ack: message reached their client (double gray ticks).
     *
     * @param  int[]  $messageIds
     */
    public function markDelivered(User $user, Conversation $conversation, array $messageIds): int
    {
        $this->assertParticipant($user, $conversation);

        $ids = collect($messageIds)->map(fn ($id) => (int) $id)->unique()->values()->all();
        if ($ids === []) {
            return 0;
        }

        $now = now();
        $deliverable = [];

        // Hot-cache messages not yet in DB.
        if ($this->outbox->isActive()) {
            foreach ($ids as $id) {
                $hot = $this->outbox->getHotMessage($id);
                if (! $hot) {
                    continue;
                }
                if ((int) $hot['conversation_id'] !== (int) $conversation->id) {
                    continue;
                }
                if ((int) $hot['user_id'] === (int) $user->id) {
                    continue;
                }
                if (! empty($hot['delivered_at'])) {
                    continue;
                }
                $hot['delivered_at'] = $now->toDateTimeString();
                $this->outbox->cacheHotMessage($hot);
                $deliverable[] = $id;
            }
        }

        $dbIds = Message::query()
            ->visibleTo($user)
            ->where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->whereIn('id', $ids)
            ->whereNull('delivered_at')
            ->pluck('id')
            ->all();

        $deliverable = array_values(array_unique(array_merge($deliverable, $dbIds)));
        if ($deliverable === []) {
            return 0;
        }

        if ($this->outbox->isActive()) {
            $this->outbox->enqueue([
                'op' => 'message.delivered',
                'message_ids' => $deliverable,
                'delivered_at' => $now->toDateTimeString(),
            ]);
            $this->maybeFlushOutboxAfterResponse();
        } else {
            Message::query()
                ->whereIn('id', $deliverable)
                ->whereNull('delivered_at')
                ->update(['delivered_at' => $now]);
        }

        // Notify senders (and the recipient's other devices) for tick updates.
        $this->notifyAllParticipants(
            $conversation,
            'message.delivered',
            [
                'conversation_id' => $conversation->id,
                'message_ids' => $deliverable,
                'delivered_at' => $now->toIso8601String(),
                'recipient_id' => $user->id,
            ],
            false // ephemeral — durable sync does not need delivery receipts
        );

        return count($deliverable);
    }

    /**
     * Broadcast a lightweight presence activity (typing / recording / uploading).
     */
    public function sendTyping(User $user, Conversation $conversation, string $activity = 'typing'): void
    {
        $this->assertParticipant($user, $conversation);

        $activity = $this->normalizeTypingActivity($activity);
        $ttl = $this->typingActivityTtl($activity);
        $this->bus->setTyping($conversation->id, $user->id, $activity, $ttl);

        // Keep Redis warm on heartbeats; fan-out when activity changes or throttle allows.
        if (! $this->bus->claimTypingBroadcast($conversation->id, $user->id, $activity, 2)) {
            return;
        }

        $this->notifyConversationParticipants(
            $conversation,
            $user->id,
            'typing',
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'activity' => $activity,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'username' => $user->username,
                ],
            ],
            false
        );
    }

    protected function normalizeTypingActivity(string $activity): string
    {
        $activity = strtolower(trim($activity));
        $allowed = [
            'typing',
            'recording_voice',
            'uploading_photo',
            'uploading_video',
            'uploading_audio',
            'uploading_file',
        ];

        return in_array($activity, $allowed, true) ? $activity : 'typing';
    }

    protected function typingActivityTtl(string $activity): int
    {
        return match ($activity) {
            'recording_voice' => 5,
            'uploading_photo', 'uploading_video', 'uploading_audio', 'uploading_file' => 8,
            default => 4,
        };
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

        $ids = MessagePin::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->orderBy('message_id')
            ->pluck('message_id');

        if ($ids->isEmpty()) {
            return new Collection;
        }

        return Message::query()
            ->visibleTo($user)
            ->whereIn('id', $ids)
            ->with($this->messageRelations($user))
            ->orderBy('id')
            ->get();
    }

    /**
     * Pin a message for myself, or for every participant when $forEveryone.
     */
    public function pinMessage(User $user, Message $message, bool $forEveryone = false): void
    {
        $message = $this->visibleMessageOrFail($user, $message->id);
        $conversation = $message->conversation;
        $this->assertParticipant($user, $conversation);

        // Groups/channels: pinning for everyone requires pin permission.
        if ($conversation->isCommunity()) {
            $this->groups()->assertCanPin($user, $conversation);
            $forEveryone = true;
        }

        $candidateRecipients = $forEveryone
            ? $conversation->users()->pluck('users.id')->all()
            : [$user->id];
        $recipients = [];

        foreach ($candidateRecipients as $uid) {
            $recipient = $uid === $user->id ? $user : User::find($uid);
            if (! $recipient || ! Message::query()->visibleTo($recipient)->whereKey($message->id)->exists()) {
                continue;
            }

            MessagePin::firstOrCreate(
                ['message_id' => $message->id, 'user_id' => $uid],
                ['conversation_id' => $conversation->id, 'pinned_by_id' => $user->id]
            );
            $recipients[] = $recipient;
        }

        foreach ($recipients as $recipient) {
            $visibleMessage = $this->visibleMessageOrFail($recipient, $message->id)
                ->load($this->messageRelations($recipient));
            $this->emitEvent($recipient->id, $conversation->id, 'message.pinned', [
                'conversation_id' => $conversation->id,
                'message' => (new MessageResource($visibleMessage))->resolve(),
            ]);
        }

        if ($conversation->isCommunity() && $forEveryone) {
            try {
                $this->groups()->audit($conversation, $user, 'message.pinned', null, [
                    'message_id' => $message->id,
                ]);
                $this->sendSystemMessage($conversation, $user, json_encode([
                    'event' => 'pinned_message',
                    'actor_id' => $user->id,
                    'message_id' => $message->id,
                ]), 'pinned_message');
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Remove a message from my own pinned list.
     */
    public function unpinMessage(User $user, Message $message): void
    {
        $message = $this->visibleMessageOrFail($user, $message->id);
        $conversation = $message->conversation;
        $this->assertParticipant($user, $conversation);

        MessagePin::where('message_id', $message->id)
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

        MessagePin::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->delete();

        $this->emitEvent($user->id, $conversation->id, 'messages.unpinned_all', [
            'conversation_id' => $conversation->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Contacts
    // -------------------------------------------------------------------------

    public function listContacts(User $user, string $sort = 'name_asc'): Collection
    {
        $sort = in_array($sort, ['name_asc', 'name_desc', 'last_seen'], true)
            ? $sort
            : 'name_asc';

        $query = Contact::query()
            ->where('contacts.user_id', $user->id)
            ->with([
                'contactUser:id,first_name,last_name,username,profile_pic,last_seen',
                'contactUser.messengerSettings',
            ]);

        if ($sort === 'last_seen') {
            $query
                ->leftJoin('users as contact_users', 'contact_users.id', '=', 'contacts.contact_user_id')
                ->orderByDesc('contact_users.last_seen')
                ->orderBy('contacts.name')
                ->select('contacts.*');
        } elseif ($sort === 'name_desc') {
            $query->orderByDesc('contacts.name');
        } else {
            $query->orderBy('contacts.name');
        }

        return $query->get();
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

        $this->privacy->invalidateUser($user->id);

        return $contact->load([
            'contactUser:id,first_name,last_name,username,profile_pic,last_seen',
            'contactUser.messengerSettings',
        ]);
    }

    public function updateContact(User $user, Contact $contact, array $data): Contact
    {
        if ((int) $contact->user_id !== (int) $user->id) {
            throw new \RuntimeException('Forbidden');
        }

        $contact->update(array_intersect_key($data, array_flip(['name', 'is_blocked', 'is_favorite'])));

        $this->privacy->invalidateUser($user->id);

        return $contact->load([
            'contactUser:id,first_name,last_name,username,profile_pic,last_seen',
            'contactUser.messengerSettings',
        ]);
    }

    public function deleteContact(User $user, Contact $contact): void
    {
        if ((int) $contact->user_id !== (int) $user->id) {
            throw new \RuntimeException('Forbidden');
        }

        $contact->delete();
        $this->privacy->invalidateUser($user->id);
    }

    public function searchUsers(User $user, string $query, int $limit = 10): Collection
    {
        $query = trim($query);
        if (mb_strlen($query) < 3) {
            return new Collection;
        }

        $mobileVariants = $this->mobileLookupVariants($query);

        return User::where('id', '!=', $user->id)
            ->where('active', true)
            ->where(function ($q) use ($query, $mobileVariants) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%");
                if ($mobileVariants !== []) {
                    $q->orWhereIn('mobile', $mobileVariants);
                }
            })
            ->select('id', 'first_name', 'last_name', 'username', 'profile_pic', 'last_seen')
            ->with('messengerSettings')
            ->limit($limit)
            ->get();
    }

    // -------------------------------------------------------------------------
    // Realtime events
    // -------------------------------------------------------------------------

    /**
     * Deliver a realtime event to a single user.
     *
     * Live transport is Laravel Reverb. Broadcasts always use ephemeral id=0 so
     * open clients apply them immediately (without waiting on /sync). Durable
     * events are write-behind via Redis when the hot path is active, otherwise
     * persisted inline for reconnect replay. Typing/presence skip persistence.
     */
    public function emitEvent(int $userId, ?int $conversationId, string $type, array $payload = [], bool $persist = true): void
    {
        if ($persist) {
            if ($this->outbox->isActive()) {
                $this->outbox->enqueue([
                    'op' => 'event.create',
                    'user_id' => $userId,
                    'conversation_id' => $conversationId,
                    'type' => $type,
                    'payload' => $payload,
                    'created_at' => now()->toDateTimeString(),
                ]);
            } else {
                MessengerEvent::create([
                    'user_id' => $userId,
                    'conversation_id' => $conversationId,
                    'type' => $type,
                    'payload' => $payload,
                    'created_at' => now(),
                ]);
            }
        }

        $broadcast = function () use ($userId, $type, $payload) {
            try {
                // Ephemeral id=0 ⇒ frontend applies immediately (see handleStreamEvent).
                broadcast(new MessengerBroadcast($userId, $type, $payload, 0));
            } catch (\Throwable $e) {
                Log::warning('Messenger broadcast failed: '.$e->getMessage());
            }
        };

        // Inside a DB transaction, wait for commit so we never leak uncommitted
        // rows. Outside a transaction (hot path / post-commit notify), push now.
        if (DB::transactionLevel() > 0) {
            DB::afterCommit($broadcast);
        } else {
            $broadcast();
        }
    }

    public function getEventsSince(
        int $userId,
        int $sinceId,
        int $limit = 50,
        ?int $highWatermark = null
    ): Collection {
        return MessengerEvent::where('user_id', $userId)
            ->where('id', '>', $sinceId)
            ->when($highWatermark !== null, fn ($query) => $query->where('id', '<=', $highWatermark))
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function getLatestCursor(int $userId): int
    {
        return (int) (MessengerEvent::where('user_id', $userId)->max('id') ?? 0);
    }

    public function totalUnreadCount(User $user): int
    {
        return (int) Message::query()
            ->visibleTo($user)
            ->where('messages.user_id', '!=', $user->id)
            ->whereNull('messages.read_at')
            ->join('conversation_user', function ($join) use ($user) {
                $join->on('conversation_user.conversation_id', '=', 'messages.conversation_id')
                    ->where('conversation_user.user_id', '=', $user->id)
                    ->whereNull('conversation_user.deleted_at');
            })
            ->where(function ($q) {
                $q->whereNull('conversation_user.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'conversation_user.last_read_at');
            })
            ->count();
    }

    public function pruneOldEvents(): int
    {
        $hours = config('messenger.event_retention_hours', 48);

        return MessengerEvent::where('created_at', '<', now()->subHours($hours))->delete();
    }

    // -------------------------------------------------------------------------
    // Wallpapers (Telegram-like gallery + per-chat / for-both)
    // -------------------------------------------------------------------------

    public function listWallpapers(User $user): Collection
    {
        return MessengerWallpaper::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    public function uploadWallpaper(User $user, UploadedFile $file): MessengerWallpaper
    {
        $diskName = config('messenger.media.disk', 'static');
        $folder = 'images/messenger/wallpapers/'.date('Y/m/d');
        $path = Storage::disk($diskName)->putFile($folder, $file);
        if (! $path) {
            throw new \RuntimeException('Failed to store wallpaper');
        }
        $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url", ''), '/');
        $url = $baseUrl.'/'.ltrim($path, '/');
        $dims = $this->readImageDimensions($file);
        $thumbPath = $this->storePhotoThumbPrivate($file, $folder, $diskName);
        $thumbUrl = $thumbPath ? $baseUrl.'/'.ltrim($thumbPath, '/') : null;

        return MessengerWallpaper::create([
            'user_id' => $user->id,
            'path' => mb_substr($path, 0, 500),
            'url' => $url,
            'thumb_url' => $thumbUrl,
            'mime' => $file->getMimeType() ?: 'image/jpeg',
            'size' => (int) $file->getSize(),
            'width' => $dims[0] ?? null,
            'height' => $dims[1] ?? null,
        ]);
    }

    public function deleteWallpaper(User $user, MessengerWallpaper $wallpaper): void
    {
        if ((int) $wallpaper->user_id !== (int) $user->id) {
            throw (new ModelNotFoundException)->setModel(MessengerWallpaper::class, [$wallpaper->id]);
        }

        $meta = [
            'url' => $wallpaper->url,
            'thumb_url' => $wallpaper->thumb_url,
        ];
        $wallpaper->delete();

        try {
            DeleteMessengerMedia::dispatch($meta, 'photo')->afterResponse();
        } catch (\Throwable $e) {
            /* noop */
        }
    }

    public function getConversationWallpaper(User $user, Conversation $conversation): ?array
    {
        $this->assertParticipant($user, $conversation);

        $row = MessengerConversationWallpaper::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->first();

        if (! $row || ! is_array($row->config) || ! $row->config) {
            return null;
        }

        return [
            'config' => $row->config,
            'for_both' => (bool) $row->for_both,
            'wallpaper_id' => $row->wallpaper_id,
            'shared_from_user_id' => $row->shared_from_user_id,
        ];
    }

    /**
     * Set (or clear) wallpaper for this chat.
     * When $forBoth is true (private peer, or community staff), the same config
     * is applied to other participants, broadcast in realtime, and a system
     * badge is posted ("X changed the chat wallpaper").
     */
    public function setConversationWallpaper(
        User $user,
        Conversation $conversation,
        ?array $config,
        bool $forBoth = false,
        ?int $wallpaperId = null
    ): array {
        $this->assertParticipant($user, $conversation);

        if ($forBoth) {
            if ($conversation->type === Conversation::TYPE_PRIVATE) {
                // Either participant may share with the peer.
            } elseif ($conversation->isCommunity()) {
                // Groups/channels: only staff who can pin may set a shared wallpaper.
                $this->groups()->assertCanPin($user, $conversation);
            } else {
                $forBoth = false;
            }
        }

        if ($wallpaperId) {
            $owned = MessengerWallpaper::query()
                ->where('id', $wallpaperId)
                ->where('user_id', $user->id)
                ->exists();
            if (! $owned) {
                $wallpaperId = null;
            }
        }

        $config = $this->normalizeWallpaperConfig($config);
        if ($config === null) {
            MessengerConversationWallpaper::query()
                ->where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->delete();

            if ($forBoth) {
                $this->applyWallpaperToPeers($user, $conversation, null, false, null);
                $this->announceWallpaperChanged($user, $conversation, true);
            }

            $this->notifyAllParticipants(
                $conversation,
                'conversation.wallpaper',
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                    'for_both' => $forBoth,
                    'config' => null,
                ],
                false
            );

            return ['config' => null, 'for_both' => false];
        }

        if ($wallpaperId) {
            $config['wallpaper_id'] = $wallpaperId;
            $config['type'] = 'custom';
            $wp = MessengerWallpaper::find($wallpaperId);
            if ($wp) {
                $config['url'] = $wp->url;
            }
        }

        MessengerConversationWallpaper::updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ],
            [
                'wallpaper_id' => $wallpaperId,
                'config' => $config,
                'for_both' => $forBoth,
                'shared_from_user_id' => null,
            ]
        );

        if ($forBoth) {
            $this->applyWallpaperToPeers($user, $conversation, $config, true, $wallpaperId);
            $this->announceWallpaperChanged($user, $conversation, false);
        }

        $this->notifyAllParticipants(
            $conversation,
            'conversation.wallpaper',
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'for_both' => $forBoth,
                'config' => $config,
            ],
            false
        );

        return [
            'config' => $config,
            'for_both' => $forBoth,
            'wallpaper_id' => $wallpaperId,
        ];
    }

    /** Centered service badge: "{name} changed the chat wallpaper". */
    protected function announceWallpaperChanged(User $actor, Conversation $conversation, bool $cleared = false): void
    {
        $name = trim("{$actor->first_name} {$actor->last_name}") ?: ($actor->username ?: '');
        $event = $cleared ? 'wallpaper_removed' : 'wallpaper_changed';

        try {
            $this->sendSystemMessage($conversation, $actor, json_encode([
                'event' => $event,
                'actor_id' => $actor->id,
                'actor_name' => $name,
                'actor_username' => $actor->username,
            ], JSON_UNESCAPED_UNICODE), $event);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    protected function applyWallpaperToPeers(
        User $actor,
        Conversation $conversation,
        ?array $config,
        bool $forBoth,
        ?int $wallpaperId
    ): void {
        $peerIds = $conversation->users()
            ->where('users.id', '!=', $actor->id)
            ->whereNull('conversation_user.deleted_at')
            ->pluck('users.id');

        foreach ($peerIds as $peerId) {
            if ($config === null) {
                MessengerConversationWallpaper::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('user_id', $peerId)
                    ->delete();
                continue;
            }

            MessengerConversationWallpaper::updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'user_id' => $peerId,
                ],
                [
                    'wallpaper_id' => null, // peer may not own the gallery row
                    'config' => $config,
                    'for_both' => $forBoth,
                    'shared_from_user_id' => $actor->id,
                ]
            );
        }
    }

    protected function normalizeWallpaperConfig(?array $config): ?array
    {
        if (! is_array($config) || ! $config) {
            return null;
        }

        $type = strtolower((string) ($config['type'] ?? 'pattern'));
        if (! in_array($type, ['pattern', 'image', 'custom', 'neon'], true)) {
            $type = 'pattern';
        }

        $blurMax = $type === 'neon' ? 80 : 40;

        $out = [
            'type' => $type,
            'pattern' => mb_substr((string) ($config['pattern'] ?? 'none'), 0, 64),
            'image' => mb_substr((string) ($config['image'] ?? ''), 0, 128),
            'color' => mb_substr((string) ($config['color'] ?? '#f3e7cf'), 0, 20),
            'glow' => mb_substr((string) ($config['glow'] ?? '#3390ec'), 0, 20),
            'intensity' => max(0, min(100, (int) ($config['intensity'] ?? 46))),
            'scale' => max(50, min(200, (int) ($config['scale'] ?? 100))),
            'blur' => max(0, min($blurMax, (int) ($config['blur'] ?? ($type === 'neon' ? 48 : 0)))),
            'dim' => max(0, min(80, (int) ($config['dim'] ?? 0))),
        ];

        if ($type === 'custom') {
            $url = (string) ($config['url'] ?? '');
            if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
                return null;
            }
            $out['url'] = mb_substr($url, 0, 1000);
            if (isset($config['wallpaper_id']) && is_numeric($config['wallpaper_id'])) {
                $out['wallpaper_id'] = (int) $config['wallpaper_id'];
            }
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Settings & Profile
    // -------------------------------------------------------------------------

    public function getSettings(User $user): \App\Models\MessengerSetting
    {
        $settings = \App\Models\MessengerSetting::firstOrCreate(
            ['user_id' => $user->id],
            \App\Models\MessengerSetting::defaults()
        );
        $user->setRelation('messengerSettings', $settings);

        return $settings;
    }

    public function updateSettings(User $user, array $data): \App\Models\MessengerSetting
    {
        $settings = $this->getSettings($user);

        $beforePrivacy = $this->privacy->settingsPayload($user, $settings);
        $beforeOnline = (bool) $settings->show_online;
        $beforeLastSeen = (bool) $settings->show_last_seen;

        $payload = array_intersect_key($data, array_flip([
            'enter_to_send', 'quote_with_title', 'forward_tap_to_chat',
            'auto_download_photos', 'auto_download_videos', 'auto_download_files',
            'auto_download_voice', 'auto_download_audio',
            'wallpaper', 'wallpaper_config',
            'theme', 'locale', 'show_online', 'show_last_seen', 'show_phone', 'show_email',
        ]));

        // Map legacy booleans → Telegram-style rules when clients still send them.
        if (array_key_exists('show_online', $payload) && ! isset($data['privacy']['online'])) {
            $payload['privacy_online'] = $payload['show_online'] ? 'everybody' : 'nobody';
        }
        if (array_key_exists('show_last_seen', $payload) && ! isset($data['privacy']['last_seen'])) {
            $payload['privacy_last_seen'] = $payload['show_last_seen'] ? 'everybody' : 'nobody';
        }
        if (array_key_exists('show_phone', $payload) && ! isset($data['privacy']['phone'])) {
            $payload['privacy_phone'] = $payload['show_phone'] ? 'everybody' : 'nobody';
        }

        if (isset($data['auto_download']) && is_array($data['auto_download'])) {
            $payload['auto_download'] = \App\Models\MessengerSetting::mergeAutoDownload(
                is_array($settings->auto_download) ? $settings->auto_download : null,
                $data['auto_download']
            );
            // Keep legacy flat columns in sync with private-chat prefs.
            $private = $payload['auto_download']['private'] ?? [];
            $payload['auto_download_photos'] = (bool) ($private['photos'] ?? false);
            $payload['auto_download_videos'] = (bool) ($private['videos'] ?? false);
            $payload['auto_download_files'] = (bool) ($private['files'] ?? false);
            $payload['auto_download_voice'] = (bool) ($private['voice'] ?? false);
            $payload['auto_download_audio'] = (bool) ($private['audio'] ?? false);
        }

        if (isset($data['auto_play']) && is_array($data['auto_play'])) {
            $payload['auto_play'] = \App\Models\MessengerSetting::mergeAutoPlay(
                is_array($settings->auto_play) ? $settings->auto_play : null,
                $data['auto_play']
            );
        }

        if ($payload !== []) {
            $settings->update($payload);
        }

        if (isset($data['privacy']) && is_array($data['privacy'])) {
            $this->privacy->updatePrivacy($user, $settings, $data['privacy']);
            $settings->refresh();
        }

        $user->setRelation('messengerSettings', $settings);

        $afterPrivacy = $this->privacy->settingsPayload($user, $settings);
        $privacyChanged = $beforePrivacy !== $afterPrivacy
            || (bool) $settings->show_online !== $beforeOnline
            || (bool) $settings->show_last_seen !== $beforeLastSeen;

        if ($privacyChanged) {
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
     * Lazy resolve to avoid a circular constructor dependency with GroupChannelService.
     */
    protected function groups(): GroupChannelService
    {
        return app(GroupChannelService::class);
    }

    /**
     * Eager-load set used everywhere a message is serialized.
     */
    protected function messageRelations(User $viewer): array
    {
        return [
            'user:id,first_name,last_name,username,profile_pic,last_seen',
            'user.messengerSettings',
            'forwardedFromUser:id,first_name,last_name,username,profile_pic,last_seen',
            'forwardedFromUser.messengerSettings',
            'media',
            'replyTo' => fn ($q) => $q
                ->visibleTo($viewer)
                ->with([
                    'user:id,first_name,last_name,username,profile_pic,last_seen',
                    'user.messengerSettings',
                ]),
            'reactions',
        ];
    }

    protected function visibleMessageOrFail(User $user, int $messageId): Message
    {
        return Message::query()
            ->visibleTo($user)
            ->findOrFail($messageId);
    }

    protected function assertParticipant(User $user, Conversation $conversation): void
    {
        // Positive cache hits skip DB. Negative/miss always verify — a stale
        // Redis set must never permanently block Saved Messages / legitimate sends.
        $cached = $this->outbox->isCachedParticipant((int) $conversation->id, (int) $user->id);
        if ($cached === true) {
            return;
        }

        if (! $conversation->hasParticipant($user)) {
            throw new \RuntimeException('Forbidden');
        }

        $this->warmParticipantCache($conversation);
    }

    /**
     * Load + cache active participant ids (Redis). Safe no-op when Redis is off.
     *
     * @return int[]
     */
    public function warmParticipantCache(Conversation $conversation): array
    {
        $ids = $conversation->users()
            ->whereNull('conversation_user.deleted_at')
            ->pluck('users.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->outbox->cacheParticipantIds((int) $conversation->id, $ids);

        return $ids;
    }

    /**
     * @return int[]
     */
    protected function participantIds(Conversation $conversation): array
    {
        $cached = $this->outbox->getCachedParticipantIds((int) $conversation->id);
        if ($cached !== null) {
            return $cached;
        }

        return $this->warmParticipantCache($conversation);
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
     * Push profile field changes to every peer that shares a conversation with
     * this user (and the user's other devices) so avatars / names update live.
     */
    public function broadcastUserProfileUpdated(User $user): void
    {
        $payload = [
            'user_id' => (int) $user->id,
            'id' => (int) $user->id,
            'profile_pic' => $user->profile_pic,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'last_seen' => $user->last_seen?->toIso8601String(),
            'is_online' => true,
        ];

        $peerIds = Conversation::query()
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->with(['users:id'])
            ->get()
            ->flatMap(fn (Conversation $c) => $c->users->pluck('id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        foreach ($peerIds as $peerId) {
            $this->emitEvent($peerId, null, 'user.updated', $payload, false);
        }
    }

    /**
     * Serialize message relations against each recipient's visibility rules so
     * a hidden reply target can never leak through a realtime payload.
     *
     * E2E ciphertext (no reply) is identical for every viewer — serialize once
     * and fan out so send latency stays O(1) DB work instead of O(N) reloads.
     */
    protected function notifyMessageToAllParticipants(
        Conversation $conversation,
        Message $message,
        string $type
    ): void {
        $recipientIds = $this->participantIds($conversation);
        if ($recipientIds === []) {
            return;
        }

        $canSharePayload = (bool) $message->is_encrypted && empty($message->reply_to_id);

        // Hot-path / shared ciphertext: serialize once, fan-out by cached ids
        // — zero per-recipient DB work on the live path.
        if ($canSharePayload || ! $message->exists) {
            $shared = null;
            if (! $message->exists) {
                if ($canSharePayload) {
                    $shared = (new MessageResource($message))->resolve();
                } else {
                    $author = ($message->relationLoaded('user') && $message->user)
                        ? $message->user
                        : User::query()->find($message->user_id);
                    if ($author) {
                        $shared = $this->serializeMessageForViewer($message, $author);
                    }
                }
            } else {
                $author = User::query()->find($message->user_id);
                if ($author) {
                    $loaded = Message::query()
                        ->whereKey($message->id)
                        ->with($this->messageRelations($author))
                        ->first();
                    if ($loaded) {
                        $shared = (new MessageResource($loaded))->resolve();
                    }
                }
            }

            if (is_array($shared)) {
                foreach ($recipientIds as $recipientId) {
                    $this->emitEvent((int) $recipientId, $conversation->id, $type, [
                        'message' => $shared,
                    ]);
                }

                return;
            }
        }

        // Durable plaintext with per-viewer reply visibility — rare vs E2E hot path.
        $recipients = $conversation->users()->whereIn('users.id', $recipientIds)->get();
        foreach ($recipients as $recipient) {
            $visibleMessage = Message::query()
                ->visibleTo($recipient)
                ->whereKey($message->id)
                ->with($this->messageRelations($recipient))
                ->first();

            if (! $visibleMessage) {
                continue;
            }

            $this->emitEvent($recipient->id, $conversation->id, $type, [
                'message' => (new MessageResource($visibleMessage))->resolve(),
            ]);
        }
    }

    /**
     * Build an Eloquent Message from a Redis hot-path row (not yet persisted).
     */
    protected function hydrateMessageRow(User $viewer, array $row, bool $includeReply = true): Message
    {
        $message = new Message;
        $message->forceFill([
            'id' => (int) $row['id'],
            'conversation_id' => (int) $row['conversation_id'],
            'user_id' => (int) $row['user_id'],
            'client_id' => $row['client_id'] ?? null,
            'body' => $row['body'] ?? '',
            'type' => $row['type'] ?? 'text',
            'media_id' => isset($row['media_id']) ? (int) $row['media_id'] : null,
            'is_encrypted' => (bool) ($row['is_encrypted'] ?? false),
            'sender_device_id' => $row['sender_device_id'] ?? null,
            'e2e' => $row['e2e'] ?? null,
            'reply_to_id' => $row['reply_to_id'] ?? null,
            'reply_show_title' => (bool) ($row['reply_show_title'] ?? true),
            'forwarded_from_user_id' => $row['forwarded_from_user_id'] ?? null,
            'is_silent' => (bool) ($row['is_silent'] ?? false),
            'scheduled_at' => ! empty($row['scheduled_at']) ? Carbon::parse($row['scheduled_at']) : null,
            'auto_delete_at' => ! empty($row['auto_delete_at']) ? Carbon::parse($row['auto_delete_at']) : null,
            'mentions' => $row['mentions'] ?? null,
            'meta' => $row['meta'] ?? null,
            'read_at' => ! empty($row['read_at']) ? Carbon::parse($row['read_at']) : null,
            'delivered_at' => ! empty($row['delivered_at']) ? Carbon::parse($row['delivered_at']) : null,
            'edited_at' => ! empty($row['edited_at']) ? Carbon::parse($row['edited_at']) : null,
            'created_at' => Carbon::parse($row['created_at'] ?? now()),
            'updated_at' => Carbon::parse($row['updated_at'] ?? now()),
        ]);
        $message->exists = false;

        // Sender is almost always the author on the hot send path — reuse the
        // already-authenticated User and avoid an extra Postgres round-trip.
        if ((int) $viewer->id === (int) $row['user_id']) {
            $message->setRelation('user', $viewer);
        } else {
            $author = User::query()
                ->select('id', 'first_name', 'last_name', 'username', 'profile_pic', 'last_seen')
                ->with('messengerSettings')
                ->find((int) $row['user_id']);
            if ($author) {
                $message->setRelation('user', $author);
            }
        }

        if (! empty($row['forwarded_from_user_id'])) {
            $fwd = User::query()
                ->select('id', 'first_name', 'last_name', 'username', 'profile_pic', 'last_seen')
                ->with('messengerSettings')
                ->find((int) $row['forwarded_from_user_id']);
            $message->setRelation('forwardedFromUser', $fwd);
        } else {
            $message->setRelation('forwardedFromUser', null);
        }

        if ($includeReply && ! empty($row['reply_to_id'])) {
            $reply = null;
            if ($this->outbox->isActive()) {
                $hotReply = $this->outbox->getHotMessage((int) $row['reply_to_id']);
                if ($hotReply && (int) ($hotReply['conversation_id'] ?? 0) === (int) $row['conversation_id']) {
                    $reply = $this->hydrateMessageRow($viewer, $hotReply, false);
                }
            }
            if (! $reply) {
                $reply = Message::query()
                    ->visibleTo($viewer)
                    ->whereKey((int) $row['reply_to_id'])
                    ->with([
                        'user:id,first_name,last_name,username,profile_pic,last_seen',
                        'user.messengerSettings',
                    ])
                    ->first();
            }
            $message->setRelation('replyTo', $reply);
        } else {
            $message->setRelation('replyTo', null);
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeMessageForViewer(Message $message, User $viewer): array
    {
        // Clone relations with viewer-scoped reply visibility.
        $clone = clone $message;
        if ($message->reply_to_id) {
            $reply = Message::query()
                ->visibleTo($viewer)
                ->whereKey($message->reply_to_id)
                ->with([
                    'user:id,first_name,last_name,username,profile_pic,last_seen',
                    'user.messengerSettings',
                ])
                ->first();
            $clone->setRelation('replyTo', $reply);
        }

        return (new MessageResource($clone))->resolve();
    }

    protected function maybeFlushOutboxAfterResponse(): void
    {
        if (! $this->outbox->isActive()) {
            return;
        }

        // Never block the HTTP response on Postgres write-behind — schedule
        // flush after the client already got 200 (live delivery is Redis/Reverb).
        dispatch(function () {
            try {
                $this->outbox->flush(max(5, (int) config('messenger.flush_batch_size', 20)));
            } catch (\Throwable $e) {
                Log::warning('Messenger outbox flush failed: '.$e->getMessage());
            }
        })->afterResponse();
    }

    /**
     * Reject sending if either side has blocked the other.
     */
    protected function assertNotBlocked(User $user, Conversation $conversation): void
    {
        if ($conversation->type !== Conversation::TYPE_PRIVATE) {
            return;
        }

        $otherId = null;
        $cachedIds = $this->outbox->getCachedParticipantIds((int) $conversation->id);
        if ($cachedIds !== null) {
            foreach ($cachedIds as $id) {
                if ((int) $id !== (int) $user->id) {
                    $otherId = (int) $id;
                    break;
                }
            }
        }

        if ($otherId === null) {
            $other = $conversation->otherUser($user)
                ?? $conversation->users()->where('users.id', '!=', $user->id)->first();
            $otherId = $other?->id ? (int) $other->id : null;
        }

        if (! $otherId) {
            return;
        }

        $cached = $this->outbox->getCachedBlock((int) $user->id, $otherId);
        if ($cached === true) {
            throw new \RuntimeException('You have blocked this user');
        }
        if ($cached === false) {
            return;
        }

        $iBlocked = Contact::where('user_id', $user->id)
            ->where('contact_user_id', $otherId)
            ->where('is_blocked', true)
            ->exists();

        if ($iBlocked) {
            $this->outbox->cacheBlock((int) $user->id, $otherId, true);
            throw new \RuntimeException('You have blocked this user');
        }

        $blockedByThem = Contact::where('user_id', $otherId)
            ->where('contact_user_id', $user->id)
            ->where('is_blocked', true)
            ->exists();

        if ($blockedByThem) {
            $this->outbox->cacheBlock((int) $user->id, $otherId, true);
            throw new \RuntimeException('This user has blocked you');
        }

        $this->outbox->cacheBlock((int) $user->id, $otherId, false);
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
        $audienceIds = $this->presenceAudience($user);
        $this->privacy->warmForBroadcast($user, $audienceIds);

        // Preload audience users for mutual privacy checks (one query).
        $audienceUsers = User::query()
            ->whereIn('id', $audienceIds)
            ->select('id', 'last_seen')
            ->with('messengerSettings')
            ->get()
            ->keyBy('id');

        foreach ($audienceIds as $audienceId) {
            $viewer = $audienceUsers->get($audienceId);
            if (! $viewer) {
                continue;
            }

            $payload = [
                'user_id' => $user->id,
                'is_online' => $this->privacy->visibleOnline($user, $viewer, $online),
                'last_seen' => $this->privacy->visibleLastSeen($user, $viewer),
            ];

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
            ->with([
                'contactUser:id,first_name,last_name,username,profile_pic,last_seen',
                'contactUser.messengerSettings',
            ])
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
        $this->outbox->cacheBlock((int) $user->id, $targetUserId, true);
        $this->privacy->invalidateUser($user->id);

        return $contact->load([
            'contactUser:id,first_name,last_name,username,profile_pic,last_seen',
            'contactUser.messengerSettings',
        ]);
    }

    public function unblockUser(User $user, int $targetUserId): void
    {
        Contact::where('user_id', $user->id)
            ->where('contact_user_id', $targetUserId)
            ->update(['is_blocked' => false]);
        $this->outbox->cacheBlock((int) $user->id, $targetUserId, false);
        $this->privacy->invalidateUser($user->id);
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

        $mobileVariants = $this->mobileLookupVariants($identifier);

        return User::query()
            ->where('active', true)
            ->where(function ($q) use ($identifier, $mobileVariants) {
                $q->where('email', $identifier)
                    ->orWhere('username', ltrim($identifier, '@'));
                if ($mobileVariants !== []) {
                    $q->orWhereIn('mobile', $mobileVariants);
                }
            })
            ->with('messengerSettings')
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

    /**
     * Normalize Iranian mobile inputs to canonical +98XXXXXXXXXX (same as auth).
     * Accepts +98 / 98 / 0098 / 0 / bare 9xxxxxxxxx and Persian/Arabic digits.
     * Ignores spaces, dashes, parentheses and other formatting characters.
     */
    public function normalizeMobile(string $value): ?string
    {
        $raw = $this->toAsciiDigits(trim($value));
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) !== 10 || ! preg_match('/^9\d{9}$/', $digits)) {
            return null;
        }

        return '+98'.$digits;
    }

    /** All common DB / input variants for an Iranian mobile (for OR lookups). */
    public function mobileLookupVariants(string $value): array
    {
        $e164 = $this->normalizeMobile($value);
        if (! $e164) {
            return [];
        }
        $national = substr($e164, 3); // 9XXXXXXXXX

        return array_values(array_unique([
            $e164,            // +989123456789
            '0'.$national,    // 09123456789
            '98'.$national,   // 989123456789
            '0098'.$national, // 00989123456789
            $national,        // 9123456789
        ]));
    }

    /**
     * Sync a device address-book batch: normalize phones, match registered users,
     * upsert synced_contacts, auto-add matched users as contacts.
     *
     * @param  array<int, array{name?: string|null, phone: string}>  $entries
     * @return array{registered: array, inviteable: array, synced_count: int, matched_count: int}
     */
    public function syncDeviceContacts(User $user, array $entries): array
    {
        $byPhone = [];
        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $phoneRaw = (string) ($entry['phone'] ?? '');
            $e164 = $this->normalizeMobile($phoneRaw);
            if (! $e164 || $e164 === $this->normalizeMobile((string) $user->mobile)) {
                continue;
            }
            $name = isset($entry['name']) ? trim((string) $entry['name']) : '';
            if ($name === '') {
                $name = $e164;
            }
            // Prefer a non-empty human name if the same number appears twice.
            if (! isset($byPhone[$e164]) || ($byPhone[$e164]['name'] === $e164 && $name !== $e164)) {
                $byPhone[$e164] = ['phone' => $e164, 'name' => $name];
            }
        }

        if ($byPhone === []) {
            return [
                'registered' => [],
                'inviteable' => [],
                'synced_count' => 0,
                'matched_count' => 0,
            ];
        }

        $variantToE164 = [];
        $allVariants = [];
        foreach (array_keys($byPhone) as $e164) {
            foreach ($this->mobileLookupVariants($e164) as $variant) {
                $variantToE164[$variant] = $e164;
                $allVariants[] = $variant;
            }
        }

        $matchedUsers = User::query()
            ->where('active', true)
            ->whereIn('mobile', array_values(array_unique($allVariants)))
            ->with('messengerSettings')
            ->get();

        /** @var array<string, User> $e164ToUser */
        $e164ToUser = [];
        foreach ($matchedUsers as $matched) {
            $canonical = $this->normalizeMobile((string) $matched->mobile);
            if ($canonical) {
                $e164ToUser[$canonical] = $matched;
            }
            foreach ($this->mobileLookupVariants((string) $matched->mobile) as $variant) {
                if (isset($variantToE164[$variant])) {
                    $e164ToUser[$variantToE164[$variant]] = $matched;
                }
            }
        }

        $now = now();
        $registered = [];
        $inviteable = [];
        $matchedCount = 0;

        foreach ($byPhone as $e164 => $row) {
            $matched = $e164ToUser[$e164] ?? null;
            $matchedId = $matched?->id;

            SyncedContact::updateOrCreate(
                ['user_id' => $user->id, 'phone_normalized' => $e164],
                [
                    'name' => $row['name'],
                    'matched_user_id' => $matchedId,
                    'last_synced_at' => $now,
                ]
            );

            if ($matched && (int) $matched->id !== (int) $user->id) {
                $matchedCount++;
                $contact = $this->addContact($user, (int) $matched->id, $row['name']);
                $registered[] = [
                    'name' => $row['name'],
                    'phone' => $e164,
                    'user' => new \App\Http\Resources\Messenger\UserBriefResource($matched),
                    'contact_id' => $contact->id,
                ];
            } else {
                $inviteable[] = [
                    'name' => $row['name'],
                    'phone' => $e164,
                    'channel' => 'sms',
                ];
            }
        }

        // Refresh registration status for previously synced numbers not in this batch.
        $this->refreshSyncedMatches($user, array_keys($byPhone));

        return [
            'registered' => $registered,
            'inviteable' => $inviteable,
            'synced_count' => count($byPhone),
            'matched_count' => $matchedCount,
        ];
    }

    /**
     * Re-match stored synced contacts against users (periodic status update).
     *
     * @param  array<int, string>|null  $skipPhones  Phones already handled in the current batch
     * @return array{registered: array, inviteable: array, synced_count: int, matched_count: int}
     */
    public function refreshSyncedContacts(User $user, ?array $skipPhones = null): array
    {
        return $this->refreshSyncedMatches($user, $skipPhones ?? []);
    }

    /**
     * @param  array<int, string>  $skipPhones
     * @return array{registered: array, inviteable: array, synced_count: int, matched_count: int}
     */
    protected function refreshSyncedMatches(User $user, array $skipPhones): array
    {
        $skip = array_flip($skipPhones);
        $rows = SyncedContact::query()
            ->where('user_id', $user->id)
            ->when($skip !== [], fn ($q) => $q->whereNotIn('phone_normalized', array_keys($skip)))
            ->get();

        if ($rows->isEmpty()) {
            return [
                'registered' => [],
                'inviteable' => [],
                'synced_count' => 0,
                'matched_count' => 0,
            ];
        }

        $variantToE164 = [];
        $allVariants = [];
        foreach ($rows as $row) {
            foreach ($this->mobileLookupVariants($row->phone_normalized) as $variant) {
                $variantToE164[$variant] = $row->phone_normalized;
                $allVariants[] = $variant;
            }
        }

        $matchedUsers = User::query()
            ->where('active', true)
            ->whereIn('mobile', array_values(array_unique($allVariants)))
            ->with('messengerSettings')
            ->get();

        $e164ToUser = [];
        foreach ($matchedUsers as $matched) {
            foreach ($this->mobileLookupVariants((string) $matched->mobile) as $variant) {
                if (isset($variantToE164[$variant])) {
                    $e164ToUser[$variantToE164[$variant]] = $matched;
                }
            }
        }

        $registered = [];
        $inviteable = [];
        $matchedCount = 0;
        $now = now();

        foreach ($rows as $row) {
            $matched = $e164ToUser[$row->phone_normalized] ?? null;
            $matchedId = $matched?->id;
            $row->matched_user_id = $matchedId;
            $row->last_synced_at = $now;
            $row->save();

            if ($matched && (int) $matched->id !== (int) $user->id) {
                $matchedCount++;
                $contact = $this->addContact($user, (int) $matched->id, $row->name);
                $registered[] = [
                    'name' => $row->name,
                    'phone' => $row->phone_normalized,
                    'user' => new \App\Http\Resources\Messenger\UserBriefResource($matched),
                    'contact_id' => $contact->id,
                ];
            } else {
                $inviteable[] = [
                    'name' => $row->name,
                    'phone' => $row->phone_normalized,
                    'channel' => 'sms',
                ];
            }
        }

        return [
            'registered' => $registered,
            'inviteable' => $inviteable,
            'synced_count' => $rows->count(),
            'matched_count' => $matchedCount,
        ];
    }

    /**
     * List stored synced contacts split into registered / inviteable.
     *
     * @return array{registered: array, inviteable: array, last_synced_at: ?string}
     */
    public function listSyncedContacts(User $user): array
    {
        $rows = SyncedContact::query()
            ->where('user_id', $user->id)
            ->with(['matchedUser:id,first_name,last_name,username,profile_pic,last_seen', 'matchedUser.messengerSettings'])
            ->orderBy('name')
            ->get();

        $registered = [];
        $inviteable = [];
        $lastSynced = null;

        foreach ($rows as $row) {
            if ($row->last_synced_at && (! $lastSynced || $row->last_synced_at->gt($lastSynced))) {
                $lastSynced = $row->last_synced_at;
            }
            if ($row->matchedUser) {
                $registered[] = [
                    'id' => $row->id,
                    'name' => $row->name,
                    'phone' => $row->phone_normalized,
                    'user' => new \App\Http\Resources\Messenger\UserBriefResource($row->matchedUser),
                ];
            } else {
                $inviteable[] = [
                    'id' => $row->id,
                    'name' => $row->name,
                    'phone' => $row->phone_normalized,
                    'channel' => 'sms',
                ];
            }
        }

        return [
            'registered' => $registered,
            'inviteable' => $inviteable,
            'last_synced_at' => $lastSynced?->toIso8601String(),
        ];
    }

    protected function toAsciiDigits(string $value): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($arabic, $latin, str_replace($persian, $latin, $value));
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
