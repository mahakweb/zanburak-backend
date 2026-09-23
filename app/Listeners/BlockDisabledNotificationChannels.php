<?php

namespace App\Listeners;

use App\Services\NotificationChannelGate;
use Illuminate\Notifications\Events\NotificationSending;

class BlockDisabledNotificationChannels
{
    public function __construct(private NotificationChannelGate $gate)
    {
    }

    public function handle(NotificationSending $event): bool
    {
        return $this->gate->allows(
            $event->notifiable,
            $event->channel,
            $event->notification ? get_class($event->notification) : null
        );
    }
}
