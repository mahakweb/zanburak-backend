<?php

namespace App\Events\Chat;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingIndicator implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $targetUserId;
    public $isTyping;

    public function __construct(User $user, $targetUserId, $isTyping)
    {
        $this->user = $user;
        $this->targetUserId = $targetUserId;
        $this->isTyping = $isTyping;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('private-chat.' . $this->targetUserId);
    }

    public function broadcastAs()
    {
        return 'TypingIndicator';
    }
}
