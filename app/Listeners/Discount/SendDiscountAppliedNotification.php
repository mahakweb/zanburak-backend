<?php

namespace App\Listeners\Discount;

use App\Events\Discount\DiscountApplied;

class SendDiscountAppliedNotification
{
    public function handle(DiscountApplied $event)
    {
        $event->user->notify(
            new \App\Notifications\Discount\DiscountNotification(
                $event->message,
                $event->actionUrl
            )
        );
    }
}
