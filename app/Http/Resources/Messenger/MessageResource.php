<?php

namespace App\Http\Resources\Messenger;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'body' => $this->body,
            'type' => $this->type,
            'reply_to_id' => $this->reply_to_id,
            'reply_show_title' => (bool) $this->reply_show_title,
            'read_at' => $this->read_at?->toIso8601String(),
            'edited_at' => $this->edited_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => new UserBriefResource($this->whenLoaded('user')),
            'forwarded_from' => $this->whenLoaded('forwardedFromUser', fn () => $this->forwardedFromUser
                ? new UserBriefResource($this->forwardedFromUser)
                : null),
            'reply_to' => $this->whenLoaded('replyTo', function () {
                if (! $this->replyTo) {
                    return null;
                }

                return [
                    'id' => $this->replyTo->id,
                    'user_id' => $this->replyTo->user_id,
                    'body' => $this->replyTo->trashed() ? null : $this->replyTo->body,
                    'deleted' => $this->replyTo->trashed(),
                    'user' => $this->replyTo->relationLoaded('user') && $this->replyTo->user
                        ? new UserBriefResource($this->replyTo->user)
                        : null,
                ];
            }),
        ];
    }
}
