<?php

namespace App\Http\Resources\Messenger;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $me = $request->user();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'users' => UserBriefResource::collection($this->whenLoaded('users')),
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
                $me && $this->relationLoaded('users'),
                fn () => new UserBriefResource($this->otherUser($me))
            ),
            'pivot' => $this->when(
                $me && $this->relationLoaded('users'),
                function () use ($me) {
                    $pivot = $this->users->firstWhere('id', $me->id)?->pivot;

                    return $pivot ? [
                        'last_read_at' => $pivot->last_read_at,
                        'cleared_at' => $pivot->cleared_at,
                        'deleted_at' => $pivot->deleted_at,
                        'muted_at' => $pivot->muted_at,
                    ] : null;
                }
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
