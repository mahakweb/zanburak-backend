<?php

namespace App\Events\Settlement;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SettlementChangedBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $settlementId,
        public ?int $teacherId,
        public string $status,
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('settlements')];
        if ($this->teacherId) {
            $channels[] = new PrivateChannel('settlement.user.'.$this->teacherId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'settlement.changed';
    }
};
