<?php

// app/Events/NewMessage.php
namespace App\Events\Chat;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $user;

    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->user = $message->receiver;
    }

    public function broadcastOn()
    {
        // dd($this->user->id);
        return new PrivateChannel('private-chat.'.$this->user->id);
        // return ['chat'];
    }

    public function broadcastAs()
    {
        return 'NewMessage';
    }
}
