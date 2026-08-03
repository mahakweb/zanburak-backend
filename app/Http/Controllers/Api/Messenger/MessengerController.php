<?php

namespace App\Http\Controllers\Api\Messenger;

use App\Http\Controllers\Controller;
use App\Http\Resources\Messenger\ConversationResource;
use App\Http\Resources\Messenger\MessageResource;
use App\Http\Resources\Messenger\UserBriefResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessengerWallpaper;
use App\Services\Messenger\MessengerService;
use App\Services\Messenger\MessengerSystemConfig;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MessengerController extends Controller
{
    public function __construct(
        protected MessengerService $messenger,
        protected MessengerSystemConfig $systemConfig
    ) {}

    public function systemConfig(Request $request): JsonResponse
    {
        return response()->json($this->systemConfig->clientConfig($request->user()));
    }

    public function conversations(Request $request): JsonResponse
    {
        $paginator = $this->messenger->listConversations($request->user());

        return response()->json([
            'data' => ConversationResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function searchMessages(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:200',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        if (empty($filters['q']) && empty($filters['from']) && empty($filters['to'])) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'total' => 0,
                    'has_more' => false,
                ],
            ]);
        }

        $paginator = $this->messenger->searchAllMessages($request->user(), $filters);
        $me = $request->user();

        $data = collect($paginator->items())->map(function (Message $message) use ($me) {
            $row = (new MessageResource($message))->toArray(request());
            $conversation = $message->conversation;
            if ($conversation) {
                $isCommunity = $conversation->isCommunity();
                $row['conversation'] = [
                    'id' => $conversation->id,
                    'type' => $conversation->type,
                    'title' => $isCommunity ? $conversation->title : null,
                    'avatar' => $isCommunity ? $conversation->avatar : null,
                    'username' => $isCommunity ? $conversation->username : null,
                    'partner' => (! $isCommunity && $conversation->type !== Conversation::TYPE_SAVED)
                        ? (($other = $conversation->otherUser($me)) ? new UserBriefResource($other) : null)
                        : null,
                    'users' => UserBriefResource::collection(
                        $conversation->relationLoaded('users') ? $conversation->users : []
                    ),
                ];
            }

            return $row;
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function createConversation(Request $request): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_private_chats')) {
            return response()->json(['message' => 'چت خصوصی غیرفعال است.'], 403);
        }

        $request->validate(['user_id' => 'required|integer|exists:users,id']);

        try {
            $conversation = $this->messenger->findOrCreateConversation(
                $request->user(),
                (int) $request->input('user_id')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new ConversationResource($conversation));
    }

    public function savedConversation(Request $request): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_saved_messages')) {
            return response()->json(['message' => 'پیام‌های ذخیره‌شده غیرفعال است.'], 403);
        }

        $conversation = $this->messenger->getOrCreateSavedConversation($request->user());
        $conversation->setResponseAttribute('unread_count', 0);

        return response()->json(new ConversationResource($conversation));
    }

    public function showConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        $isMember = $conversation->hasParticipant($user);
        if (! $isMember) {
            if (! $conversation->isCommunity() || ! $conversation->is_public) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
        }

        $conversation->load([
            'users:id,first_name,last_name,username,profile_pic,last_seen',
            'users.messengerSettings',
            'owner:id,first_name,last_name,username,profile_pic',
        ]);
        if ($isMember) {
            $conversation->setRelation(
                'lastMessage',
                $this->messenger->lastVisibleMessageFor($user, $conversation)
            );
        }
        $conversation->setResponseAttribute('my_role', $isMember ? $conversation->memberRole($user) : null);
        $conversation->setResponseAttribute('is_preview', ! $isMember);

        if ($conversation->isCommunity()) {
            app(\App\Services\Messenger\GroupChannelService::class)
                ->refreshMemberCount($conversation);
            // Do NOT $conversation->refresh() here — it wipes response-only attrs
            // like is_preview / unread_count and makes non-member opens look joined.
            if ($isMember) {
                $role = $conversation->memberRole($user);
                $conversation->setResponseAttribute('my_role', $role);
                $conversation->setResponseAttribute(
                    'permissions',
                    app(\App\Services\Messenger\GroupPermissionService::class)
                        ->matrixFor($conversation)[$role] ?? []
                );
            } else {
                $conversation->setResponseAttribute('my_role', null);
                $conversation->setResponseAttribute('permissions', []);
                $conversation->setResponseAttribute('is_preview', true);
            }
        }

        // After any DB writes — unread_count is response-only, not a column.
        $conversation->setResponseAttribute(
            'unread_count',
            $isMember ? $conversation->unreadCountFor($user) : 0
        );

        return response()->json(new ConversationResource($conversation));
    }

    public function deleteConversation(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $this->messenger->deleteConversation($request->user(), $conversation);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function clearConversation(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $this->messenger->clearConversation($request->user(), $conversation);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function muteConversation(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate(['mute' => 'sometimes|boolean']);

        try {
            $this->messenger->muteConversation(
                $request->user(),
                $conversation,
                $request->boolean('mute', true)
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $paginator = $this->messenger->getMessages(
                $request->user(),
                $conversation,
                $request->integer('before_id') ?: null
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'data' => MessageResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => null,
                'per_page' => $paginator->perPage(),
                'total' => null,
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function sharedMedia(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|string|in:photo,video,gif,audio,voice,links,media,file',
            'before_id' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $result = $this->messenger->getSharedMedia(
                $request->user(),
                $conversation,
                $data['type'],
                isset($data['before_id']) ? (int) $data['before_id'] : null,
                (int) ($data['limit'] ?? 40)
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'data' => MessageResource::collection($result['messages']),
            'meta' => [
                'type' => $data['type'],
                'has_more' => $result['has_more'],
            ],
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $maxLen = $this->systemConfig->int('max_message_length')
            ?: (int) config('messenger.max_message_length', 5000);

        $request->validate([
            'body' => 'nullable|string|max:'.((int) config('messenger.e2e.max_ciphertext_length', 65536)),
            'client_id' => 'sometimes|string|max:64',
            'reply_to_id' => 'sometimes|nullable|integer',
            'reply_show_title' => 'sometimes|boolean',
            'type' => 'sometimes|string|in:text,location,photo,video,voice,audio,file',
            'meta' => 'sometimes|nullable|array',
            'meta.lat' => 'required_if:type,location|numeric|between:-90,90',
            'meta.lng' => 'required_if:type,location|numeric|between:-180,180',
            'meta.accuracy' => 'sometimes|nullable|numeric|min:0',
            'is_encrypted' => 'sometimes|boolean',
            'sender_device_id' => 'required_if:is_encrypted,true|nullable|string|max:64',
            'e2e' => 'sometimes|nullable|array',
            'e2e.v' => 'sometimes|integer|min:1',
            'e2e.alg' => 'sometimes|string|max:32',
            'e2e.iv' => 'sometimes|string|max:128',
            'e2e.kid' => 'sometimes|integer|min:1',
            'e2e.aad' => 'sometimes|integer|min:0',
            'e2e.sid' => 'sometimes|string|max:64',
            'e2e.wraps' => 'sometimes|array|max:32',
            'e2e.wraps.*.did' => 'required_with:e2e.wraps|string|max:64',
            'e2e.wraps.*.iv' => 'required_with:e2e.wraps|string|max:128',
            'e2e.wraps.*.ct' => 'required_with:e2e.wraps|string|max:512',
            'e2e.wraps.*.sap' => 'required_with:e2e.wraps|string|max:512',
            'mention_ids' => 'sometimes|array|max:50',
            'mention_ids.*' => 'integer|min:1',
        ]);

        $type = $request->input('type', 'text');
        $meta = $request->input('meta');
        $isEncrypted = $request->boolean('is_encrypted');
        if ($type === 'location' && is_array($meta) && ! $isEncrypted) {
            $meta = [
                'lat' => round((float) $meta['lat'], 6),
                'lng' => round((float) $meta['lng'], 6),
                'accuracy' => isset($meta['accuracy']) ? round((float) $meta['accuracy'], 1) : null,
            ];
        }
        if ($isEncrypted) {
            // Location coordinates live inside ciphertext; strip clear meta.
            $meta = is_array($meta) ? array_intersect_key($meta, array_flip(['album_id', 'album_index', 'album_count', 'silent', 'animation'])) : null;
        }

        // Plain JSON send is for text/location only. Media must use /media (multipart).
        if (in_array($type, ['photo', 'video', 'voice', 'audio', 'file'], true)) {
            return response()->json(['message' => 'Use the media upload endpoint to send files'], 422);
        }

        if ($type === 'location' && ! $this->systemConfig->bool('allow_location')) {
            return response()->json(['message' => 'ارسال موقعیت مکانی غیرفعال است.'], 403);
        }

        $body = (string) $request->input('body', '');
        if ($type === 'text' && trim($body) === '') {
            return response()->json(['message' => 'Message body cannot be empty'], 422);
        }

        $maxLen = $isEncrypted
            ? (int) config('messenger.e2e.max_ciphertext_length', 65536)
            : ($this->systemConfig->int('max_message_length') ?: (int) config('messenger.max_message_length', 5000));
        if (mb_strlen($body) > $maxLen) {
            return response()->json(['message' => "Message exceeds maximum length of {$maxLen} characters"], 422);
        }

        try {
            $message = $this->messenger->sendMessage(
                $request->user(),
                $conversation,
                $body,
                $request->input('client_id'),
                [
                    'reply_to_id' => $request->input('reply_to_id'),
                    'reply_show_title' => $request->boolean('reply_show_title', true),
                    'type' => $type,
                    'meta' => $meta,
                    'is_encrypted' => $isEncrypted,
                    'sender_device_id' => $request->input('sender_device_id'),
                    'e2e' => $request->input('e2e'),
                    'mention_ids' => $request->input('mention_ids'),
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Message not found'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new MessageResource($message), 201);
    }

    public function sendMedia(Request $request, Conversation $conversation): JsonResponse
    {
        $cfg = config('messenger.media', []);
        $type = $request->input('type');

        $maxMap = [
            'photo' => $this->systemConfig->mediaMaxKb('photo'),
            'video' => $this->systemConfig->mediaMaxKb('video'),
            'audio' => $this->systemConfig->mediaMaxKb('audio'),
            'voice' => $this->systemConfig->mediaMaxKb('voice'),
            'file' => $this->systemConfig->mediaMaxKb('file'),
        ];
        $mimeMap = [
            'photo' => $cfg['photo_mimes'] ?? ['jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp'],
            'video' => $cfg['video_mimes'] ?? ['mp4', 'webm', 'mov', 'm4v', '3gp'],
            'audio' => $cfg['audio_mimes'] ?? ['mp3', 'm4a', 'aac', 'ogg', 'wav', 'flac', 'opus'],
            'voice' => $cfg['voice_mimes'] ?? ['webm', 'ogg', 'mp4', 'm4a', 'aac', 'opus'],
            'file' => $cfg['file_mimes'] ?? [],
        ];
        $blockedFile = $cfg['file_blocked_mimes'] ?? [
            'exe', 'bat', 'cmd', 'com', 'scr', 'msi', 'msp', 'pif',
            'vbs', 'vbe', 'wsf', 'wsh', 'ps1', 'psc1',
            'dll', 'sys', 'drv', 'cpl', 'reg', 'inf', 'lnk', 'url', 'jar', 'jnlp',
        ];

        if (! isset($maxMap[$type])) {
            return response()->json(['message' => 'Invalid media type'], 422);
        }

        if (! $this->systemConfig->isMediaTypeAllowed((string) $type)) {
            return response()->json([
                'message' => $this->systemConfig->bool('uploads_enabled')
                    ? 'آپلود این نوع فایل مجاز نیست.'
                    : 'آپلود فایل در پیام‌رسان غیرفعال است.',
            ], 403);
        }

        $maxKb = $maxMap[$type];
        $maxAlbum = max(1, $this->systemConfig->int('max_album_items'));

        $request->validate([
            'type' => 'required|string|in:photo,video,voice,audio,file',
            'file' => "required|file|max:{$maxKb}",
            'caption' => 'sometimes|nullable|string|max:'.((int) config('messenger.e2e.max_ciphertext_length', 65536)),
            'client_id' => 'sometimes|string|max:64',
            'reply_to_id' => 'sometimes|nullable|integer',
            'reply_show_title' => 'sometimes|boolean',
            'duration' => 'sometimes|nullable|numeric|min:0|max:86400',
            'width' => 'sometimes|nullable|integer|min:1|max:10000',
            'height' => 'sometimes|nullable|integer|min:1|max:10000',
            'cover' => 'sometimes|nullable|image|max:2048|mimes:jpeg,jpg,png,webp',
            'silent' => 'sometimes|boolean',
            'animation' => 'sometimes|boolean',
            'album_id' => 'sometimes|nullable|string|max:64',
            'album_index' => 'sometimes|nullable|integer|min:0|max:'.$maxAlbum,
            'album_count' => 'sometimes|nullable|integer|min:1|max:'.$maxAlbum,
            'is_encrypted' => 'sometimes|boolean',
            'sender_device_id' => 'required_if:is_encrypted,true|nullable|string|max:64',
            'e2e' => 'sometimes|nullable',
            'encrypted' => 'sometimes|boolean',
        ]);

        $file = $request->file('file');
        $isEncrypted = $request->boolean('is_encrypted') || $request->boolean('encrypted');
        $e2e = $request->input('e2e');
        if (is_string($e2e)) {
            $decoded = json_decode($e2e, true);
            $e2e = is_array($decoded) ? $decoded : null;
        }

        try {
            $this->systemConfig->assertUploadAllowed($request->user(), (string) $type, (int) $file->getSize());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $guess = strtolower((string) $file->guessExtension());
        $resolvedExt = $ext ?: $guess;

        if ($isEncrypted) {
            // Ciphertext uploads are opaque; skip mime allowlists.
        } elseif ($type === 'file') {
            // Telegram-like documents: deny dangerous executables; allowlisted + other safe unknown exts.
            if ($resolvedExt && in_array($resolvedExt, $blockedFile, true)) {
                return response()->json(['message' => 'This file type is not allowed'], 422);
            }
        } else {
            $allowed = $mimeMap[$type];
            // Browsers often omit/guess odd extensions for MediaRecorder blobs.
            if ($ext && ! in_array($ext, $allowed, true)) {
                if (! in_array($guess, $allowed, true) && ! ($type === 'voice' && in_array($guess, ['webm', 'ogg', 'mp4'], true))) {
                    return response()->json(['message' => 'Unsupported file format'], 422);
                }
            }
        }

        $asAnimation = $request->boolean('silent') || $request->boolean('animation');

        try {
            $message = $this->messenger->sendMediaMessage(
                $request->user(),
                $conversation,
                $file,
                $type,
                (string) $request->input('caption', ''),
                $request->input('client_id'),
                [
                    'reply_to_id' => $request->input('reply_to_id'),
                    'reply_show_title' => $request->boolean('reply_show_title', true),
                    'duration' => $request->input('duration'),
                    'width' => $request->input('width'),
                    'height' => $request->input('height'),
                    'cover' => $isEncrypted ? null : $request->file('cover'),
                    'silent' => $asAnimation,
                    'animation' => $asAnimation,
                    'album_id' => $request->input('album_id'),
                    'album_index' => $request->input('album_index'),
                    'album_count' => $request->input('album_count'),
                    'is_encrypted' => $isEncrypted,
                    'encrypted' => $isEncrypted,
                    'sender_device_id' => $request->input('sender_device_id'),
                    'e2e' => $e2e,
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Message not found'], 404);
        } catch (\Throwable $e) {
            report($e);

            $raw = $e->getMessage();
            $isQuota = stripos($raw, 'quota') !== false
                || stripos($raw, 'Disk quota exceeded') !== false;

            return response()->json([
                'message' => $isQuota
                    ? 'فضای ذخیره‌سازی فایل‌ها پر است. لطفاً بعداً دوباره تلاش کنید.'
                    : 'خطا در آپلود فایل. اتصال یا فضای سرور استاتیک را بررسی کنید.',
            ], 500);
        }

        $this->systemConfig->recordUpload($request->user(), (int) $file->getSize());

        return response()->json(new MessageResource($message), 201);
    }

    public function forwardMessages(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_forward')) {
            return response()->json(['message' => 'فوروارد پیام غیرفعال است.'], 403);
        }

        $request->validate([
            'message_ids' => 'required|array|min:1|max:50',
            'message_ids.*' => 'integer',
            'drop_author' => 'sometimes|boolean',
        ]);

        try {
            $messages = $this->messenger->forwardMessages(
                $request->user(),
                $request->input('message_ids'),
                $conversation,
                $request->boolean('drop_author', false)
            );
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Message not found'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'data' => MessageResource::collection($messages),
        ], 201);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_delete_messages')) {
            return response()->json(['message' => 'حذف پیام غیرفعال است.'], 403);
        }

        $request->validate([
            'message_ids' => 'required|array|min:1|max:100',
            'message_ids.*' => 'integer',
            'scope' => 'sometimes|in:me,everyone',
        ]);

        try {
            $deleted = $this->messenger->bulkDeleteMessages(
                $request->user(),
                $request->input('message_ids'),
                $request->input('scope', 'everyone')
            );
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Message not found'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['deleted' => $deleted]);
    }

    public function editMessage(Request $request, Message $message): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_edit_messages')) {
            return response()->json(['message' => 'ویرایش پیام غیرفعال است.'], 403);
        }

        $maxLen = (int) config('messenger.e2e.max_ciphertext_length', 65536);
        $request->validate([
            'body' => 'nullable|string|max:'.$maxLen,
            'is_encrypted' => 'sometimes|boolean',
            'sender_device_id' => 'sometimes|nullable|string|max:64',
            'e2e' => 'sometimes|nullable|array',
        ]);

        $window = $this->systemConfig->int('edit_window_minutes');
        if ($window > 0 && $message->created_at && $message->created_at->lt(now()->subMinutes($window))) {
            return response()->json(['message' => "مهلت ویرایش پیام ({$window} دقیقه) به پایان رسیده است."], 403);
        }

        try {
            $message = $this->messenger->editMessage(
                $request->user(),
                $message,
                (string) $request->input('body', ''),
                [
                    'is_encrypted' => $request->boolean('is_encrypted'),
                    'sender_device_id' => $request->input('sender_device_id'),
                    'e2e' => $request->input('e2e'),
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Message not found'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new MessageResource($message));
    }

    public function deleteMessage(Request $request, Message $message): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_delete_messages')) {
            return response()->json(['message' => 'حذف پیام غیرفعال است.'], 403);
        }

        $request->validate(['scope' => 'sometimes|in:me,everyone']);

        try {
            $this->messenger->deleteMessage(
                $request->user(),
                $message,
                $request->input('scope', 'everyone')
            );
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Message not found'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function pins(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $messages = $this->messenger->pinnedMessagesFor($request->user(), $conversation);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'data' => MessageResource::collection($messages),
        ]);
    }

    public function pinMessage(Request $request, Message $message): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_pin_messages')) {
            return response()->json(['message' => 'سنجاق پیام غیرفعال است.'], 403);
        }

        $request->validate(['for_everyone' => 'sometimes|boolean']);

        try {
            $this->messenger->pinMessage(
                $request->user(),
                $message,
                $request->boolean('for_everyone', false)
            );
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Message not found'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function unpinMessage(Request $request, Message $message): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_pin_messages')) {
            return response()->json(['message' => 'سنجاق پیام غیرفعال است.'], 403);
        }

        try {
            $this->messenger->unpinMessage($request->user(), $message);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Message not found'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function unpinAll(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_pin_messages')) {
            return response()->json(['message' => 'سنجاق پیام غیرفعال است.'], 403);
        }

        try {
            $this->messenger->unpinAll($request->user(), $conversation);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function presencePing(Request $request): JsonResponse
    {
        $this->messenger->pingPresence($request->user());

        return response()->json(['ok' => true]);
    }

    public function presenceOffline(Request $request): JsonResponse
    {
        $this->messenger->setOffline($request->user());

        return response()->json(['ok' => true]);
    }

    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $activity = (string) $request->input('activity', 'typing');

        try {
            $this->messenger->sendTyping($request->user(), $conversation, $activity);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $updated = $this->messenger->markRead($request->user(), $conversation);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['updated' => $updated]);
    }

    public function markDelivered(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'message_ids' => 'required|array|min:1|max:100',
            'message_ids.*' => 'integer',
        ]);

        try {
            $updated = $this->messenger->markDelivered(
                $request->user(),
                $conversation,
                $request->input('message_ids', [])
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['updated' => $updated]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->messenger->totalUnreadCount($request->user()),
        ]);
    }

    public function getSettings(Request $request): JsonResponse
    {
        $settings = $this->messenger->getSettings($request->user());

        return response()->json($this->settingsPayload($settings));
    }

    public function updateSettings(Request $request): JsonResponse
    {
        if (
            (! $this->systemConfig->bool('allow_wallpapers'))
            && ($request->has('wallpaper') || $request->has('wallpaper_config'))
        ) {
            return response()->json(['message' => 'پس‌زمینه چت غیرفعال است.'], 403);
        }

        $data = $request->validate([
            'enter_to_send' => 'sometimes|boolean',
            'quote_with_title' => 'sometimes|boolean',
            'forward_tap_to_chat' => 'sometimes|boolean',
            'wallpaper' => 'sometimes|nullable|string|max:40',
            'wallpaper_config' => 'sometimes|nullable|array',
            'wallpaper_config.type' => 'sometimes|string|in:pattern,image,custom',
            'wallpaper_config.pattern' => 'sometimes|nullable|string|max:64',
            'wallpaper_config.image' => 'sometimes|nullable|string|max:128',
            'wallpaper_config.color' => 'sometimes|nullable|string|max:20',
            'wallpaper_config.intensity' => 'sometimes|integer|min:0|max:100',
            'wallpaper_config.blur' => 'sometimes|integer|min:0|max:40',
            'wallpaper_config.dim' => 'sometimes|integer|min:0|max:80',
            'wallpaper_config.url' => 'sometimes|nullable|string|max:1000',
            'wallpaper_config.wallpaper_id' => 'sometimes|nullable|integer',
            'theme' => 'sometimes|nullable|string|max:20',
            'locale' => 'sometimes|nullable|string|max:5',
            'show_online' => 'sometimes|boolean',
            'show_last_seen' => 'sometimes|boolean',
            'show_phone' => 'sometimes|boolean',
            'show_email' => 'sometimes|boolean',
        ]);

        $settings = $this->messenger->updateSettings($request->user(), $data);

        return response()->json($this->settingsPayload($settings));
    }

    public function listWallpapers(Request $request): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_wallpapers')) {
            return response()->json(['message' => 'پس‌زمینه چت غیرفعال است.'], 403);
        }

        $items = $this->messenger->listWallpapers($request->user())
            ->map(fn (MessengerWallpaper $w) => $w->toApiArray())
            ->values();

        return response()->json(['data' => $items]);
    }

    public function uploadWallpaper(Request $request): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_wallpapers') || ! $this->systemConfig->bool('allow_custom_wallpapers')) {
            return response()->json(['message' => 'آپلود پس‌زمینه سفارشی غیرفعال است.'], 403);
        }

        $maxKb = max(100, $this->systemConfig->int('max_wallpaper_kb'));
        $data = $request->validate([
            'file' => "required|file|image|max:{$maxKb}",
        ]);

        $wallpaper = $this->messenger->uploadWallpaper($request->user(), $data['file']);

        return response()->json($wallpaper->toApiArray(), 201);
    }

    public function deleteWallpaper(Request $request, MessengerWallpaper $wallpaper): JsonResponse
    {
        $this->messenger->deleteWallpaper($request->user(), $wallpaper);

        return response()->json(['ok' => true]);
    }

    public function getConversationWallpaper(Request $request, Conversation $conversation): JsonResponse
    {
        $payload = $this->messenger->getConversationWallpaper($request->user(), $conversation);

        return response()->json($payload ?: ['config' => null]);
    }

    public function setConversationWallpaper(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_wallpapers')) {
            return response()->json(['message' => 'پس‌زمینه چت غیرفعال است.'], 403);
        }

        $data = $request->validate([
            'config' => 'nullable|array',
            'config.type' => 'sometimes|string|in:pattern,image,custom',
            'config.pattern' => 'sometimes|nullable|string|max:64',
            'config.image' => 'sometimes|nullable|string|max:128',
            'config.color' => 'sometimes|nullable|string|max:20',
            'config.intensity' => 'sometimes|integer|min:0|max:100',
            'config.blur' => 'sometimes|integer|min:0|max:40',
            'config.dim' => 'sometimes|integer|min:0|max:80',
            'config.url' => 'sometimes|nullable|string|max:1000',
            'config.wallpaper_id' => 'sometimes|nullable|integer',
            'for_both' => 'sometimes|boolean',
            'wallpaper_id' => 'sometimes|nullable|integer',
        ]);

        $result = $this->messenger->setConversationWallpaper(
            $request->user(),
            $conversation,
            $data['config'] ?? null,
            (bool) ($data['for_both'] ?? false),
            isset($data['wallpaper_id']) ? (int) $data['wallpaper_id'] : null
        );

        return response()->json($result);
    }

    private function settingsPayload(\App\Models\MessengerSetting $settings): array
    {
        return [
            'enter_to_send' => $settings->enter_to_send,
            'quote_with_title' => $settings->quote_with_title,
            'forward_tap_to_chat' => $settings->forward_tap_to_chat,
            'wallpaper' => $settings->wallpaper,
            'wallpaper_config' => $settings->wallpaper_config,
            'theme' => $settings->theme,
            'locale' => $settings->locale,
            'show_online' => $settings->show_online,
            'show_last_seen' => $settings->show_last_seen,
            'show_phone' => $settings->show_phone,
            'show_email' => $settings->show_email,
        ];
    }

    public function myProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'profile_pic' => $user->profile_pic,
            'cover_pic' => $user->cover_pic,
            'bio' => $user->bio,
        ]);
    }

    public function updateMyProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => 'sometimes|string|min:2|max:255',
            'last_name' => 'sometimes|string|min:2|max:255',
            'username' => 'sometimes|string|min:3|max:50|alpha_dash|unique:users,username,'.$user->id,
            'bio' => 'sometimes|nullable|string|max:255',
        ]);

        $user = $this->messenger->updateMyProfile($user, $data);

        return response()->json([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'profile_pic' => $user->profile_pic,
            'cover_pic' => $user->cover_pic,
            'bio' => $user->bio,
        ]);
    }

    public function checkUsername(Request $request): JsonResponse
    {
        $user = $request->user();
        $raw = trim((string) $request->input('username', ''));
        $raw = ltrim($raw, '@');

        $validator = Validator::make(
            ['username' => $raw],
            [
                'username' => [
                    'required',
                    'string',
                    'min:3',
                    'max:50',
                    'alpha_dash',
                    Rule::unique('users', 'username')->ignore($user->id),
                ],
            ]
        );

        if ($validator->fails()) {
            $failed = $validator->failed();
            $reason = isset($failed['username']['Unique']) ? 'taken' : 'invalid';

            return response()->json([
                'available' => false,
                'username' => $raw !== '' ? $raw : null,
                'reason' => $reason,
            ]);
        }

        return response()->json([
            'available' => true,
            'username' => $raw,
            'reason' => null,
        ]);
    }

    public function userProfile(Request $request, int $userId): JsonResponse
    {
        try {
            $user = $this->messenger->getUserProfile($request->user(), $userId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $settings = $user->resolvedMessengerSettings();
        $isSelf = $request->user() && (int) $request->user()->id === (int) $user->id;

        return response()->json([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'profile_pic' => $user->profile_pic,
            'cover_pic' => $user->cover_pic,
            'bio' => $user->bio,
            'last_seen' => $user->lastSeenVisible(),
            'is_online' => $user->isOnlineVisible(),
            'mobile' => ($isSelf || $settings->show_phone) ? $user->mobile : null,
            'email' => ($isSelf || $settings->show_email) ? $user->email : null,
        ]);
    }

    /**
     * Lightweight polling endpoint for clients that cannot use SSE.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'since' => ['nullable', 'regex:/^\d+$/'],
        ]);
        $sinceId = (int) ($validated['since'] ?? 0);
        $highWatermark = $this->messenger->getLatestCursor($request->user()->id);
        $events = $this->messenger->getEventsSince(
            $request->user()->id,
            $sinceId,
            51,
            $highWatermark
        );
        $hasMore = $events->count() > 50;
        $events = $events->take(50)->values();
        $cursor = $events->isNotEmpty() ? (string) $events->last()->id : (string) $sinceId;

        return response()->json([
            'events' => $events->map(fn ($e) => [
                'id' => (string) $e->id,
                'type' => $e->type,
                'conversation_id' => $e->conversation_id,
                'payload' => $e->payload,
                'created_at' => $e->created_at?->toIso8601String(),
            ]),
            // Keep "cursor" for existing consumers, but make it the last row
            // actually returned so paginated clients cannot skip durable events.
            'cursor' => $cursor,
            'has_more' => $hasMore,
            'high_watermark' => (string) $highWatermark,
        ]);
    }
}
