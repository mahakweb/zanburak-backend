<?php

namespace App\Events\Messenger;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Single self-hosted realtime event delivered to a specific user's private
 * channel over Laravel Reverb. The "type" discriminates the payload
 * (message.new, message.updated, message.deleted, messages.read, typing).
 *
 * Implements ShouldBroadcastNow so it is delivered immediately without
 * requiring a queue worker to be running.
 */
class MessengerBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $eventType,
        public array $payload = [],
        public int $eventId = 0
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("messenger.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'MessengerEvent';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->eventType,
            'data' => $this->payload,
            'id' => $this->eventId,
        ];
    }
}
