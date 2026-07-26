<?php

namespace App\Services\Messenger;

use App\Events\Messenger\MessengerBroadcast;
use App\Http\Resources\Messenger\MessageResource;
use App\Models\Contact;
use App\Models\ContactInvite;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessagePin;
use App\Models\MessengerEvent;
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
        protected MessengerOutbox $outbox
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
            ->whereIn('conversation_id', $conversationIds)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->selectRaw('conversation_id, COUNT(*) as aggregate')
            ->groupBy('conversation_id')
            ->pluck('aggregate', 'conversation_id');

        foreach ($paginator->getCollection() as $conversation) {
            $latest = $latestByConversation->get($conversation->id);
            $conversation->setResponseAttribute(
                'unread_count',
                (int) ($unreadByConversation[$conversation->id] ?? 0)
            );
            $conversation->setRelation('lastMessage', $latest);

            // Keep the existing response fields while avoiding 30-message
            // history loads for every sidebar row. The client fetches history
            // when a conversation is opened.
            $conversation->setRelation('recentMessages', new Collection);
            $conversation->setResponseAttribute('messages_has_more', $latest !== null);

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
     */
    public function getOrCreateSavedConversation(User $me): Conversation
    {
        $existing = Conversation::where('type', 'saved')
            ->whereHas('users', fn ($q) => $q->where('users.id', $me->id))
            ->first();

        if ($existing) {
            // Restore visibility in case the user had hidden it.
            $existing->users()->updateExistingPivot($me->id, ['deleted_at' => null]);

            $existing->load([
                'users:id,first_name,last_name,username,profile_pic,last_seen',
                'users.messengerSettings',
            ]);
            $existing->setRelation('lastMessage', $this->lastVisibleMessageFor($me, $existing));

            return $existing;
        }

        return DB::transaction(function () use ($me) {
            $conversation = Conversation::create(['type' => 'saved']);
            $conversation->users()->attach([$me->id]);

            return $conversation->load([
                'users:id,first_name,last_name,username,profile_pic,last_seen',
                'users.messengerSettings',
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
                ->orderByDesc('id');
        } else {
            $query = $this->visibleMessagesQuery($user, $conversation);
        }

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        // A cursor-like "before_id" request does not need an expensive total
        // count. Fetch one extra row to derive has_more.
        $perPage = (int) config('messenger.messages_per_page', 40);
        $rows = $query->limit($perPage + 1)->get();

        // Merge not-yet-flushed Redis hot messages into the latest page so a
        // refresh never hides a message that already arrived over the live path.
        if ($isMember && ! $beforeId && $this->outbox->isActive()) {
            $hot = collect($this->outbox->hotMessagesForConversation($conversation->id))
                ->map(fn (array $row) => $this->hydrateMessageRow($user, $row));
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

        // Return chronological order (oldest first) for chat UI
        $paginator->setCollection(
            $paginator->getCollection()->sortBy('id')->values()
        );

        return $paginator;
    }

    /**
     * Shared media for a conversation (Telegram-style profile tabs).
     *
     * Filters: photo | video | gif | audio | voice | links
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
                $query->where('type', Message::TYPE_PHOTO);
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
            case 'audio':
                $query->where('type', Message::TYPE_AUDIO);
                break;
            case 'voice':
                $query->where('type', Message::TYPE_VOICE);
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
        $this->groups()->assertCanSend($user, $conversation, $body);

        $msgType = $options['type'] ?? Message::TYPE_TEXT;
        $body = trim($body);
        $isMedia = in_array($msgType, Message::MEDIA_TYPES, true);
        if ($body === '' && ! $isMedia) {
            throw new \InvalidArgumentException('Message body cannot be empty');
        }

        $maxLen = config('messenger.max_message_length', 5000);
        if (mb_strlen($body) > $maxLen) {
            throw new \InvalidArgumentException("Message exceeds maximum length of {$maxLen} characters");
        }

        // Channels: replies are disabled
        if ($conversation->isChannel() && ! empty($options['reply_to_id'])) {
            throw new \InvalidArgumentException('Replies are disabled in channels');
        }

        // Idempotent send via client_id (Redis cache first, then DB)
        if ($clientId) {
            if ($this->outbox->isActive()) {
                $hotId = $this->outbox->findMessageIdByClient($conversation->id, $user->id, $clientId);
                if ($hotId) {
                    $hot = $this->outbox->getHotMessage($hotId);
                    if ($hot) {
                        return $this->hydrateMessageRow($user, $hot);
                    }
                }
            }

            $existing = Message::where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->where('client_id', $clientId)
                ->first();

            if ($existing) {
                return $this->visibleMessageOrFail($user, $existing->id)
                    ->load($this->messageRelations($user));
            }
        }

        // A hidden, cleared, deleted, or cross-conversation reply target is
        // indistinguishable from an unknown ID.
        $replyToId = $options['reply_to_id'] ?? null;
        if ($replyToId) {
            $reply = Message::query()
                ->visibleTo($user)
                ->whereKey($replyToId)
                ->where('conversation_id', $conversation->id)
                ->first();
            $reply ?? throw (new ModelNotFoundException)->setModel(Message::class, [$replyToId]);
        }

        $mentions = $conversation->isCommunity()
            ? $this->groups()->parseMentions($body)
            : [];

        $attributes = [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'client_id' => $clientId ?? Str::uuid()->toString(),
            'body' => $body,
            'type' => $msgType,
            'reply_to_id' => $replyToId,
            'reply_show_title' => $options['reply_show_title'] ?? true,
            'forwarded_from_user_id' => $options['forwarded_from_user_id'] ?? null,
            'is_silent' => (bool) ($options['is_silent'] ?? false),
            'scheduled_at' => $options['scheduled_at'] ?? null,
            'auto_delete_at' => $options['auto_delete_at'] ?? null,
            'mentions' => $mentions ?: null,
            'meta' => $options['meta'] ?? null,
        ];

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

        // Hot Redis path is for plain text only; location/media (and other typed
        // messages) go through the durable DB write so meta is persisted.
        if ($this->outbox->isActive() && $msgType === 'text') {
            $message = $this->sendMessageHot($user, $conversation, $attributes);
        } else {
            $message = $this->sendMessageDurable($user, $conversation, $attributes);
        }

        $this->groups()->onMessageSent($user, $conversation);

        return $message;
    }

    /**
     * Upload a chat media file and create a photo/video/voice/audio message.
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

        return $this->sendMessage($user, $conversation, $caption, $clientId, array_merge($options, [
            'type' => $type,
            'meta' => array_merge($options['meta'] ?? [], $meta),
        ]));
    }

    /**
     * Persist an uploaded media file to the CDN disk and build message meta.
     *
     * @return array<string, mixed>
     */
    protected function storeMediaFile(UploadedFile $file, string $type, array $options = []): array
    {
        $cfg = config('messenger.media', []);
        $diskName = $cfg['disk'] ?? 'static';
        $folder = trim($cfg['folder'] ?? 'messenger/media', '/').'/'.$type.'/'.date('Y/m/d');

        $path = Storage::disk($diskName)->putFile($folder, $file);
        if (! $path) {
            throw new \RuntimeException('Failed to store media file');
        }

        $baseUrl = rtrim(config("filesystems.disks.{$diskName}.url", ''), '/');
        $url = $baseUrl.'/'.ltrim($path, '/');

        $origName = $file->getClientOriginalName() ?: ('media.'.$file->getClientOriginalExtension());
        $ext = strtolower($file->getClientOriginalExtension() ?: pathinfo($origName, PATHINFO_EXTENSION));
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $size = (int) $file->getSize();

        $meta = [
            'url' => $url,
            'mime' => $mime,
            'size' => $size,
            'name' => mb_substr($origName, 0, 255),
            'ext' => $ext,
        ];

        if (isset($options['width']) && is_numeric($options['width'])) {
            $meta['width'] = (int) $options['width'];
        }
        if (isset($options['height']) && is_numeric($options['height'])) {
            $meta['height'] = (int) $options['height'];
        }
        if (isset($options['duration']) && is_numeric($options['duration'])) {
            $meta['duration'] = round((float) $options['duration'], 1);
        }

        // Silent / GIF-like looping video (Telegram animation style).
        if (! empty($options['silent']) || ! empty($options['animation'])) {
            $meta['silent'] = true;
            $meta['animation'] = true;
        }

        // Grouped media album (Telegram media_group style).
        if (! empty($options['album_id']) && is_string($options['album_id'])) {
            $meta['album_id'] = mb_substr($options['album_id'], 0, 64);
            if (isset($options['album_index']) && is_numeric($options['album_index'])) {
                $meta['album_index'] = (int) $options['album_index'];
            }
            if (isset($options['album_count']) && is_numeric($options['album_count'])) {
                $meta['album_count'] = (int) $options['album_count'];
            }
        }

        if ($type === Message::TYPE_PHOTO) {
            $dims = $this->readImageDimensions($file);
            if ($dims) {
                $meta['width'] = $meta['width'] ?? $dims[0];
                $meta['height'] = $meta['height'] ?? $dims[1];
            }
            $thumbUrl = $this->storePhotoThumb($file, $folder, $diskName, $baseUrl);
            if ($thumbUrl) {
                $meta['thumb_url'] = $thumbUrl;
            }
        }

        if ($type === Message::TYPE_AUDIO) {
            $cover = $options['cover'] ?? null;
            if ($cover instanceof UploadedFile && $cover->isValid()) {
                $coverUrl = $this->storeAudioCover($cover, $folder, $diskName, $baseUrl);
                if ($coverUrl) {
                    $meta['cover_url'] = $coverUrl;
                }
            }
        }

        return $meta;
    }

    /**
     * Store an optional album-art image next to the audio file.
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
     * Tiny low-quality JPEG used as the pre-download (blurred) preview.
     */
    protected function storePhotoThumb(UploadedFile $file, string $folder, string $diskName, string $baseUrl): ?string
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

            return $baseUrl.'/'.ltrim($thumbPath, '/');
        } catch (\Throwable $e) {
            Log::warning('messenger.thumb_failed', ['error' => $e->getMessage()]);

            return null;
        }
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
        $row = array_merge($attributes, [
            'id' => $id,
            'read_at' => null,
            'delivered_at' => null,
            'edited_at' => null,
            'deleted_at' => null,
            'created_at' => $now->toDateTimeString(),
            'updated_at' => $now->toDateTimeString(),
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
     * Classic DB-first send (fallback when Redis is off / unreachable).
     */
    protected function sendMessageDurable(User $user, Conversation $conversation, array $attributes): Message
    {
        $message = DB::transaction(function () use ($attributes, $conversation) {
            $message = Message::create($attributes);

            $conversation->update([
                'last_message_id' => $message->id,
                'last_message_at' => now(),
            ]);

            // Restore conversation for all participants who had deleted it
            DB::table('conversation_user')
                ->where('conversation_id', $conversation->id)
                ->whereNotNull('deleted_at')
                ->update(['deleted_at' => null, 'updated_at' => now()]);

            return $message;
        });

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
            ->with('conversation')
            ->get()
            ->keyBy('id');

        if ($sources->count() !== $uniqueIds->count()) {
            throw (new ModelNotFoundException)->setModel(Message::class, $uniqueIds->all());
        }

        return DB::transaction(function () use ($user, $messageIds, $target, $dropAuthor, $sources) {
            $created = [];
            foreach ($messageIds as $messageId) {
                $source = $sources->get((int) $messageId);
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
                } elseif (in_array($sourceType, Message::MEDIA_TYPES, true)) {
                    $srcMeta = is_array($source->meta) ? $source->meta : [];
                    $mediaMeta = array_filter([
                        'url' => $srcMeta['url'] ?? null,
                        'thumb_url' => $srcMeta['thumb_url'] ?? null,
                        'mime' => $srcMeta['mime'] ?? null,
                        'size' => $srcMeta['size'] ?? null,
                        'name' => $srcMeta['name'] ?? null,
                        'ext' => $srcMeta['ext'] ?? null,
                        'width' => $srcMeta['width'] ?? null,
                        'height' => $srcMeta['height'] ?? null,
                        'duration' => $srcMeta['duration'] ?? null,
                    ], fn ($v) => $v !== null && $v !== '');
                    $options['meta'] = array_merge($options['meta'] ?? [], $mediaMeta);
                }

                $created[] = $this->sendMessage($user, $target, $source->body ?? '', null, $options);
            }

            return $created;
        });
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

    public function editMessage(User $user, Message $message, string $body): Message
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

        $body = trim($body);
        if ($body === '' && ! $message->isMedia()) {
            throw new \InvalidArgumentException('Message body cannot be empty');
        }

        $mentions = $conversation->isCommunity()
            ? $this->groups()->parseMentions($body)
            : null;

        $message->update([
            'body' => $body,
            'edited_at' => now(),
            'mentions' => $mentions,
        ]);

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

        // Pins reference this message; clear them for all participants.
        MessagePin::where('message_id', $messageId)->delete();

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

        if ($this->outbox->isActive()) {
            // Count unread from DB + treat any hot incoming messages as unread.
            $updated = Message::query()
                ->visibleTo($user)
                ->where('conversation_id', $conversation->id)
                ->where('user_id', '!=', $user->id)
                ->whereNull('read_at')
                ->count();

            $hotIncoming = collect($this->outbox->hotMessagesForConversation($conversation->id))
                ->filter(fn (array $row) => (int) $row['user_id'] !== (int) $user->id && empty($row['read_at']))
                ->count();

            $updated += $hotIncoming;

            if ($updated > 0) {
                $readAt = now()->toDateTimeString();
                $this->outbox->enqueue([
                    'op' => 'messages.read',
                    'conversation_id' => $conversation->id,
                    'reader_id' => $user->id,
                    'read_at' => $readAt,
                ]);

                // Stamp hot-cache rows so a subsequent merge does not resurrect unread.
                foreach ($this->outbox->hotMessagesForConversation($conversation->id) as $row) {
                    if ((int) $row['user_id'] === (int) $user->id) {
                        continue;
                    }
                    $row['read_at'] = $readAt;
                    $this->outbox->cacheHotMessage($row);
                }

                $this->notifyAllParticipants(
                    $conversation,
                    'messages.read',
                    [
                        'conversation_id' => $conversation->id,
                        'reader_id' => $user->id,
                        'count' => $updated,
                    ]
                );

                $this->maybeFlushOutboxAfterResponse();
            }

            return $updated;
        }

        $updated = Message::query()
            ->visibleTo($user)
            ->where('conversation_id', $conversation->id)
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
    }

    public function searchUsers(User $user, string $query, int $limit = 10): Collection
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return new Collection;
        }

        return User::where('id', '!=', $user->id)
            ->where('active', true)
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%");
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
        return Message::query()
            ->visibleTo($user)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
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
     * Serialize message relations against each recipient's visibility rules so
     * a hidden reply target can never leak through a realtime payload.
     */
    protected function notifyMessageToAllParticipants(
        Conversation $conversation,
        Message $message,
        string $type
    ): void {
        foreach ($conversation->users()->get() as $recipient) {
            // Hot-path messages are not in Postgres yet — serialize from the
            // in-memory model (reply visibility already validated for sender).
            if (! $message->exists) {
                $payloadMessage = $this->serializeMessageForViewer($message, $recipient);
                $this->emitEvent($recipient->id, $conversation->id, $type, [
                    'message' => $payloadMessage,
                ]);

                continue;
            }

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
    protected function hydrateMessageRow(User $viewer, array $row): Message
    {
        $message = new Message;
        $message->forceFill([
            'id' => (int) $row['id'],
            'conversation_id' => (int) $row['conversation_id'],
            'user_id' => (int) $row['user_id'],
            'client_id' => $row['client_id'] ?? null,
            'body' => $row['body'] ?? '',
            'type' => $row['type'] ?? 'text',
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

        $author = User::query()
            ->select('id', 'first_name', 'last_name', 'username', 'profile_pic', 'last_seen')
            ->with('messengerSettings')
            ->find((int) $row['user_id']);
        if ($author) {
            $message->setRelation('user', $author);
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

        if (! empty($row['reply_to_id'])) {
            $reply = Message::query()
                ->visibleTo($viewer)
                ->whereKey((int) $row['reply_to_id'])
                ->with([
                    'user:id,first_name,last_name,username,profile_pic,last_seen',
                    'user.messengerSettings',
                ])
                ->first();
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
        if (! $this->outbox->shouldFlushNow()) {
            return;
        }

        dispatch(function () {
            try {
                app(MessengerOutbox::class)->flush((int) config('messenger.flush_batch_size', 20) * 2);
            } catch (\Throwable $e) {
                Log::warning('Messenger opportunistic flush failed: '.$e->getMessage());
            }
        })->afterResponse();
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
