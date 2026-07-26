<?php

namespace App\Http\Resources\Messenger;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $me = $request->user();
        $isCommunity = in_array($this->type, ['group', 'channel'], true);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->when($isCommunity, $this->title),
            'description' => $this->when($isCommunity, $this->description),
            'avatar' => $this->when($isCommunity, $this->avatar),
            'cover' => $this->when($isCommunity, $this->cover),
            'username' => $this->when($isCommunity, $this->username),
            'is_public' => $this->when($isCommunity, (bool) $this->is_public),
            'owner_id' => $this->when($isCommunity, $this->owner_id),
            'member_count' => $this->when($isCommunity, (int) $this->member_count),
            'message_count' => $this->when($isCommunity, (int) $this->message_count),
            'is_verified' => $this->when($isCommunity, (bool) $this->is_verified),
            'is_archived' => $this->when($isCommunity, (bool) $this->is_archived),
            'slow_mode_seconds' => $this->when($isCommunity, (int) $this->slow_mode_seconds),
            'history_visible' => $this->when($isCommunity, (bool) $this->history_visible),
            'join_approval_required' => $this->when($isCommunity, (bool) $this->join_approval_required),
            'reactions_enabled' => $this->when($isCommunity, (bool) $this->reactions_enabled),
            'comments_enabled' => $this->when($isCommunity, (bool) $this->comments_enabled),
            'signatures_enabled' => $this->when($isCommunity, (bool) $this->signatures_enabled),
            'who_can_send' => $this->when($isCommunity, $this->who_can_send),
            'who_can_invite' => $this->when($isCommunity, $this->who_can_invite),
            'who_can_pin' => $this->when($isCommunity, $this->who_can_pin),
            'who_can_edit_info' => $this->when($isCommunity, $this->who_can_edit_info),
            'messages_locked' => $this->when($isCommunity, (bool) $this->messages_locked),
            'messages_locked_until' => $this->when($isCommunity, $this->messages_locked_until?->toIso8601String()),
            'lock_schedule' => $this->when($isCommunity, $this->lock_schedule),
            'chat_locked_now' => $this->when($isCommunity, fn () => $this->isChatLockedNow()),
            'my_role' => $this->when($isCommunity && $me, function () use ($me) {
                return $this->my_role ?? $this->memberRole($me);
            }),
            'permissions' => $this->when($isCommunity && isset($this->permissions), $this->permissions),
            'join_request_pending' => $this->when(isset($this->join_request_pending), (bool) $this->join_request_pending),
            'is_preview' => $this->when($isCommunity, (bool) ($this->is_preview ?? false)),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'users' => UserBriefResource::collection($this->whenLoaded('users')),
            'owner' => new UserBriefResource($this->whenLoaded('owner')),
            'last_message' => new MessageResource($this->whenLoaded('lastMessage')),
            'messages' => MessageResource::collection($this->whenLoaded('recentMessages')),
            'messages_has_more' => $this->when(
                isset($this->messages_has_more),
                fn () => (bool) $this->messages_has_more
            ),
            'unread_count' => $this->when(
                isset($this->unread_count),
                fn () => (int) $this->unread_count
            ),
            'partner' => $this->when(
                $me && $this->relationLoaded('users') && ! $isCommunity,
                fn () => new UserBriefResource($this->otherUser($me))
            ),
            'pivot' => $this->when(
                $me && $this->relationLoaded('users'),
                function () use ($me) {
                    $pivot = $this->users->firstWhere('id', $me->id)?->pivot;

                    return $pivot ? [
                        'role' => $pivot->role ?? null,
                        'nickname' => $pivot->nickname ?? null,
                        'custom_title' => $pivot->custom_title ?? null,
                        'last_read_at' => $pivot->last_read_at,
                        'last_read_message_id' => $pivot->last_read_message_id ?? null,
                        'cleared_at' => $pivot->cleared_at,
                        'deleted_at' => $pivot->deleted_at,
                        'muted_at' => $pivot->muted_at,
                        'muted_until' => $pivot->muted_until ?? null,
                        'notification_mode' => $pivot->notification_mode ?? 'all',
                        'is_banned' => (bool) ($pivot->is_banned ?? false),
                        'is_active' => (bool) ($pivot->is_active ?? true),
                        'joined_at' => $pivot->joined_at ?? null,
                        'message_count' => (int) ($pivot->message_count ?? 0),
                        'badges' => is_string($pivot->badges ?? null)
                            ? (json_decode($pivot->badges, true) ?: [])
                            : ($pivot->badges ?? []),
                    ] : null;
                }
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
