<?php

namespace App\Http\Controllers\Api\Messenger;

use App\Http\Controllers\Controller;
use App\Http\Resources\Messenger\ConversationResource;
use App\Http\Resources\Messenger\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Messenger\MessengerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessengerController extends Controller
{
    public function __construct(
        protected MessengerService $messenger
    ) {}

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

    public function createConversation(Request $request): JsonResponse
    {
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
        $conversation = $this->messenger->getOrCreateSavedConversation($request->user());
        $conversation->unread_count = 0;

        return response()->json(new ConversationResource($conversation));
    }

    public function showConversation(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $conversation->hasParticipant($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $conversation->load([
            'users:id,first_name,last_name,username,profile_pic,last_seen',
            'lastMessage',
        ]);
        $conversation->unread_count = $conversation->unreadCountFor($request->user());

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
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'body' => 'required|string|max:'.config('messenger.max_message_length', 5000),
            'client_id' => 'sometimes|string|max:64',
            'reply_to_id' => 'sometimes|nullable|integer|exists:messages,id',
            'reply_show_title' => 'sometimes|boolean',
        ]);

        try {
            $message = $this->messenger->sendMessage(
                $request->user(),
                $conversation,
                $request->input('body'),
                $request->input('client_id'),
                [
                    'reply_to_id' => $request->input('reply_to_id'),
                    'reply_show_title' => $request->boolean('reply_show_title', true),
                ]
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new MessageResource($message), 201);
    }

    public function forwardMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'message_ids' => 'required|array|min:1|max:50',
            'message_ids.*' => 'integer|exists:messages,id',
            'drop_author' => 'sometimes|boolean',
        ]);

        try {
            $messages = $this->messenger->forwardMessages(
                $request->user(),
                $request->input('message_ids'),
                $conversation,
                $request->boolean('drop_author', false)
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'data' => MessageResource::collection($messages),
        ], 201);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
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
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['deleted' => $deleted]);
    }

    public function editMessage(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'body' => 'required|string|max:'.config('messenger.max_message_length', 5000),
        ]);

        try {
            $message = $this->messenger->editMessage(
                $request->user(),
                $message,
                $request->input('body')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new MessageResource($message));
    }

    public function deleteMessage(Request $request, Message $message): JsonResponse
    {
        $request->validate(['scope' => 'sometimes|in:me,everyone']);

        try {
            $this->messenger->deleteMessage(
                $request->user(),
                $message,
                $request->input('scope', 'everyone')
            );
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
        try {
            $this->messenger->sendTyping($request->user(), $conversation);
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
        $data = $request->validate([
            'enter_to_send' => 'sometimes|boolean',
            'quote_with_title' => 'sometimes|boolean',
            'wallpaper' => 'sometimes|nullable|string|max:40',
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

    private function settingsPayload(\App\Models\MessengerSetting $settings): array
    {
        return [
            'enter_to_send' => $settings->enter_to_send,
            'quote_with_title' => $settings->quote_with_title,
            'wallpaper' => $settings->wallpaper,
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
        $sinceId = $request->integer('since', 0);
        $events = $this->messenger->getEventsSince($request->user()->id, $sinceId);

        return response()->json([
            'events' => $events->map(fn ($e) => [
                'id' => $e->id,
                'type' => $e->type,
                'conversation_id' => $e->conversation_id,
                'payload' => $e->payload,
                'created_at' => $e->created_at?->toIso8601String(),
            ]),
            'cursor' => $this->messenger->getLatestCursor($request->user()->id),
        ]);
    }
}
