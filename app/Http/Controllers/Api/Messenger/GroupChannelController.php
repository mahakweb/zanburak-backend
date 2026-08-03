<?php

namespace App\Http\Controllers\Api\Messenger;

use App\Http\Controllers\Controller;
use App\Http\Resources\Messenger\ConversationResource;
use App\Http\Resources\Messenger\MessageResource;
use App\Http\Resources\Messenger\UserBriefResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Messenger\GroupChannelService;
use App\Services\Messenger\GroupPermissionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupChannelController extends Controller
{
    public function __construct(
        protected GroupChannelService $groups,
        protected GroupPermissionService $permissions,
        protected \App\Services\Messenger\MessengerSystemConfig $systemConfig
    ) {}

    // -------------------------------------------------------------------------
    // Create / update
    // -------------------------------------------------------------------------

    public function createGroup(Request $request): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_groups')) {
            return response()->json(['message' => 'ساخت گروه غیرفعال است.'], 403);
        }

        $maxMembers = max(2, $this->systemConfig->int('max_group_members'));
        $data = $request->validate([
            'title' => 'required|string|max:'.config('messenger_groups.max_title_length', 128),
            'description' => 'nullable|string|max:'.config('messenger_groups.max_description_length', 2000),
            'username' => 'nullable|string|max:32',
            'is_public' => 'sometimes|boolean',
            'history_visible' => 'sometimes|boolean',
            'join_approval_required' => 'sometimes|boolean',
            'member_ids' => 'sometimes|array|max:'.$maxMembers,
            'member_ids.*' => 'integer|exists:users,id',
            'avatar' => 'nullable|string|max:512',
            'cover' => 'nullable|string|max:512',
        ]);

        try {
            $conversation = $this->groups->createGroup(
                $request->user(),
                $data,
                $data['member_ids'] ?? []
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new ConversationResource($conversation), 201);
    }

    public function createChannel(Request $request): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_channels')) {
            return response()->json(['message' => 'ساخت کانال غیرفعال است.'], 403);
        }

        $maxSubs = $this->systemConfig->int('max_channel_subscribers');
        $maxMembers = $maxSubs > 0
            ? $maxSubs
            : max(2, $this->systemConfig->int('max_group_members'));

        $data = $request->validate([
            'title' => 'required|string|max:'.config('messenger_groups.max_title_length', 128),
            'description' => 'nullable|string|max:'.config('messenger_groups.max_description_length', 2000),
            'username' => 'nullable|string|max:32',
            'is_public' => 'sometimes|boolean',
            'join_approval_required' => 'sometimes|boolean',
            'member_ids' => 'sometimes|array|max:'.$maxMembers,
            'member_ids.*' => 'integer|exists:users,id',
            'avatar' => 'nullable|string|max:512',
            'cover' => 'nullable|string|max:512',
        ]);

        try {
            $conversation = $this->groups->createChannel(
                $request->user(),
                $data,
                $data['member_ids'] ?? []
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new ConversationResource($conversation), 201);
    }

    public function update(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:'.config('messenger_groups.max_title_length', 128),
            'description' => 'nullable|string|max:'.config('messenger_groups.max_description_length', 2000),
            'username' => 'nullable|string|max:32',
            'avatar' => 'nullable|string|max:512',
            'cover' => 'nullable|string|max:512',
            'is_public' => 'sometimes|boolean',
            'history_visible' => 'sometimes|boolean',
            'join_approval_required' => 'sometimes|boolean',
            'reactions_enabled' => 'sometimes|boolean',
            'comments_enabled' => 'sometimes|boolean',
            'signatures_enabled' => 'sometimes|boolean',
            'who_can_send' => 'sometimes|in:all,admins',
            'who_can_invite' => 'sometimes|in:all,admins',
            'who_can_pin' => 'sometimes|in:all,admins',
            'who_can_edit_info' => 'sometimes|in:all,admins',
            'slow_mode_seconds' => 'sometimes|integer|min:0|max:86400',
            'is_archived' => 'sometimes|boolean',
            'messages_locked' => 'sometimes|boolean',
            'messages_locked_until' => 'nullable|date',
            'lock_schedule' => 'nullable|array',
            'lock_schedule.enabled' => 'sometimes|boolean',
            'lock_schedule.start' => 'sometimes|string|regex:/^\d{2}:\d{2}$/',
            'lock_schedule.end' => 'sometimes|string|regex:/^\d{2}:\d{2}$/',
            'lock_schedule.timezone' => 'sometimes|string|max:64',
            'lock_schedule.days' => 'sometimes|array',
            'lock_schedule.days.*' => 'integer|min:0|max:6',
        ]);

        try {
            $conversation = $this->groups->updateInfo($request->user(), $conversation, $data);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new ConversationResource($conversation));
    }

    public function uploadImage(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate([
            'kind' => 'required|in:avatar,cover',
            'image' => 'required|image|max:10240|mimes:jpeg,jpg,png,gif,webp,bmp',
        ]);

        try {
            $conversation = $this->groups->uploadImage(
                $request->user(),
                $conversation,
                $request->file('image'),
                $request->input('kind')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new ConversationResource($conversation));
    }

    // -------------------------------------------------------------------------
    // Members
    // -------------------------------------------------------------------------

    public function members(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate(['role' => 'sometimes|string|max:20']);

        try {
            $paginator = $this->groups->listMembers(
                $request->user(),
                $conversation,
                $request->input('role')
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        $data = collect($paginator->items())->map(function ($user) {
            $pivot = $user->pivot;

            return [
                'user' => (new UserBriefResource($user))->resolve(),
                'role' => $pivot->role,
                'nickname' => $pivot->nickname,
                'custom_title' => $pivot->custom_title,
                'joined_at' => $pivot->joined_at,
                'message_count' => (int) $pivot->message_count,
                'badges' => is_string($pivot->badges) ? (json_decode($pivot->badges, true) ?: []) : ($pivot->badges ?? []),
                'muted_until' => $pivot->muted_until,
                'is_banned' => (bool) $pivot->is_banned,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function memberProfile(Request $request, Conversation $conversation, int $userId): JsonResponse
    {
        try {
            $profile = $this->groups->memberProfile($request->user(), $conversation, $userId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Not found'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json($profile);
    }

    public function updateMember(Request $request, Conversation $conversation, int $userId): JsonResponse
    {
        $data = $request->validate([
            'nickname' => 'nullable|string|max:64',
            'custom_title' => 'nullable|string|max:64',
            'role' => 'sometimes|string|max:20',
            'notification_mode' => 'sometimes|in:all,mentions,mute,custom',
        ]);

        try {
            $profile = $this->groups->updateMember($request->user(), $conversation, $userId, $data);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json($profile);
    }

    public function transferOwnership(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate(['user_id' => 'required|integer|exists:users,id']);

        try {
            $conversation = $this->groups->transferOwnership(
                $request->user(),
                $conversation,
                (int) $request->input('user_id')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new ConversationResource($conversation));
    }

    public function leave(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $this->groups->leave($request->user(), $conversation);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function kick(Request $request, Conversation $conversation, int $userId): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);

        try {
            $this->groups->kick($request->user(), $conversation, $userId, $request->input('reason'));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function addMembers(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'user_ids' => 'required|array|min:1|max:50',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        try {
            $conversation = $this->groups->addMembers(
                $request->user(),
                $conversation,
                $data['user_ids']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new ConversationResource($conversation));
    }

    // -------------------------------------------------------------------------
    // Join
    // -------------------------------------------------------------------------

    public function join(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate(['message' => 'nullable|string|max:500']);

        try {
            if ($conversation->join_approval_required && ! $conversation->hasParticipant($request->user())) {
                $conversation = $this->groups->requestJoin(
                    $request->user(),
                    $conversation,
                    $request->input('message')
                );
            } else {
                $conversation = $this->groups->joinPublic($request->user(), $conversation);
            }
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new ConversationResource($conversation));
    }

    public function joinByUsername(Request $request): JsonResponse
    {
        $request->validate(['username' => 'required|string|max:32']);

        try {
            $conversation = $this->groups->joinByUsername($request->user(), $request->input('username'));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Not found'], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new ConversationResource($conversation));
    }

    public function previewJoin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'nullable|string|max:64',
            'username' => 'nullable|string|max:32',
        ]);

        if (empty($data['code']) && empty($data['username'])) {
            return response()->json(['message' => 'code or username is required'], 422);
        }

        try {
            $preview = $this->groups->previewJoin(
                $request->user(),
                $data['code'] ?? null,
                $data['username'] ?? null
            );
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Not found'], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'conversation' => new ConversationResource($preview['conversation']),
            'is_member' => $preview['is_member'],
            'invite_code' => $preview['invite_code'],
            'can_preview' => $preview['can_preview'],
        ]);
    }

    public function joinByInvite(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|max:64']);

        try {
            $conversation = $this->groups->joinByInvite($request->user(), $request->input('code'));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Not found'], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new ConversationResource($conversation));
    }

    public function joinRequests(Request $request, Conversation $conversation): JsonResponse
    {
        $request->validate(['status' => 'sometimes|in:pending,approved,rejected,all']);

        try {
            $paginator = $this->groups->listJoinRequests(
                $request->user(),
                $conversation,
                $request->input('status', 'pending')
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function approveJoinRequest(Request $request, Conversation $conversation, int $requestId): JsonResponse
    {
        try {
            $this->groups->approveJoinRequest($request->user(), $conversation, $requestId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Not found'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function rejectJoinRequest(Request $request, Conversation $conversation, int $requestId): JsonResponse
    {
        $request->validate(['note' => 'nullable|string|max:500']);

        try {
            $this->groups->rejectJoinRequest(
                $request->user(),
                $conversation,
                $requestId,
                $request->input('note')
            );
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Not found'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Invites
    // -------------------------------------------------------------------------

    public function createInvite(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'max_uses' => 'nullable|integer|min:1|max:100000',
            'expires_at' => 'nullable|date|after:now',
            'is_temporary' => 'sometimes|boolean',
        ]);

        try {
            $invite = $this->groups->createInvite($request->user(), $conversation, $data);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json($invite, 201);
    }

    public function invites(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $invites = $this->groups->listInvites($request->user(), $conversation);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['data' => $invites]);
    }

    public function revokeInvite(Request $request, Conversation $conversation, int $inviteId): JsonResponse
    {
        try {
            $this->groups->revokeInvite($request->user(), $conversation, $inviteId);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Not found'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Ban / mute
    // -------------------------------------------------------------------------

    public function ban(Request $request, Conversation $conversation, int $userId): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
            'expires_in' => 'nullable|integer|min:60|max:31536000',
        ]);

        try {
            $ban = $this->groups->ban(
                $request->user(),
                $conversation,
                $userId,
                $data['reason'] ?? null,
                $data['expires_in'] ?? null
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json($ban);
    }

    public function unban(Request $request, Conversation $conversation, int $userId): JsonResponse
    {
        try {
            $this->groups->unban($request->user(), $conversation, $userId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function bans(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $paginator = $this->groups->listBans($request->user(), $conversation);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function mute(Request $request, Conversation $conversation, int $userId): JsonResponse
    {
        $data = $request->validate([
            'preset' => 'sometimes|in:10m,1h,8h,1d,1w,forever',
            'seconds' => 'nullable|integer|min:60|max:31536000',
        ]);

        $seconds = $data['seconds'] ?? null;
        if (isset($data['preset'])) {
            $seconds = config('messenger_groups.mute_presets.'.$data['preset']);
        }

        try {
            $this->groups->muteMember($request->user(), $conversation, $userId, $seconds);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function unmute(Request $request, Conversation $conversation, int $userId): JsonResponse
    {
        try {
            $this->groups->unmuteMember($request->user(), $conversation, $userId);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // Search / permissions / audit / reactions
    // -------------------------------------------------------------------------

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'type' => 'sometimes|in:group,channel',
        ]);

        $results = $this->groups->searchCommunities(
            $request->user(),
            $request->input('q'),
            $request->input('type')
        );

        return response()->json(['data' => $results]);
    }

    public function checkUsername(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string|max:32',
            'except_id' => 'sometimes|nullable|integer',
        ]);

        try {
            $result = $this->groups->checkUsernameAvailable(
                $request->input('username'),
                $request->integer('except_id') ?: null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'available' => false,
                'username' => null,
                'reason' => 'invalid',
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json($result);
    }

    public function searchMessages(Request $request, Conversation $conversation): JsonResponse
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:200',
            'user_id' => 'nullable|integer',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'pinned' => 'sometimes|boolean',
            'links' => 'sometimes|boolean',
        ]);

        try {
            $paginator = $this->groups->searchMessages($request->user(), $conversation, $filters);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'data' => MessageResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function permissions(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $conversation->hasParticipant($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'matrix' => $this->permissions->matrixFor($conversation),
            'my_role' => $conversation->memberRole($request->user()),
            'available' => config('messenger_groups.permissions'),
        ]);
    }

    public function setPermission(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'role' => 'required|string|max:20',
            'permission' => 'required|string|max:64',
            'allowed' => 'required|boolean',
        ]);

        try {
            $matrix = $this->groups->setRolePermission(
                $request->user(),
                $conversation,
                $data['role'],
                $data['permission'],
                $data['allowed']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['matrix' => $matrix]);
    }

    public function auditLogs(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $paginator = $this->groups->listAuditLogs($request->user(), $conversation);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    public function react(Request $request, Message $message): JsonResponse
    {
        if (! $this->systemConfig->bool('allow_reactions')) {
            return response()->json(['message' => 'واکنش به پیام غیرفعال است.'], 403);
        }

        $request->validate(['emoji' => 'required|string|max:32']);

        try {
            $result = $this->groups->toggleReaction($request->user(), $message, $request->input('emoji'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json($result);
    }

    public function view(Request $request, Message $message): JsonResponse
    {
        try {
            $count = $this->groups->recordView($request->user(), $message);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['view_count' => $count]);
    }

    public function viewBatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message_ids' => 'required|array|min:1|max:100',
            'message_ids.*' => 'integer',
        ]);

        $views = $this->groups->recordViews($request->user(), $data['message_ids']);

        return response()->json(['views' => $views]);
    }
}
