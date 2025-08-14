<?php

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

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */


    // public $message;
    public $sender;
    public $receiver;
    public $now;

    public function __construct(User $sender, User $receiver, $now)
    {
        // $this->message = $message;
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->now = $now;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // return new PrivateChannel('channel-name');
        return new PrivateChannel('private-chat.'.$this->sender->id);
    }

    public function broadcastAs()
    {
        return 'MessageRead';
    }
}
