<?php

// app/Events/DeleteMessage.php
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

class DeleteMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $user;

    public function __construct(Message $message)
    {
        $this->message = $message;

        if (auth('api')->user()->id == $message->sender_id)
            $this->user = $message->receiver;
        else
            $this->user = $message->sender;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('private-chat.'.$this->user->id);
    }

    public function broadcastAs()
    {
        return 'DeleteMessage';
    }
}
