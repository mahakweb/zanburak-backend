<?php

namespace App\Http\Resources\Messenger;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBriefResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => $this->username,
            'profile_pic' => $this->profile_pic,
            'last_seen' => $this->lastSeenVisible(),
            'is_online' => $this->isOnlineVisible(),
            // Whether this user lets others jump into a chat with them by tapping
            // their name in a forwarded-message header.
            'forward_tap_to_chat' => (bool) $this->resolvedMessengerSettings()->forward_tap_to_chat,
        ];
    }
}
