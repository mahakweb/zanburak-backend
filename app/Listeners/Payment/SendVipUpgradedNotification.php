<?php

namespace App\Listeners\Payment;

use App\Events\Payment\VipUpgraded;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendVipUpgradedNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\Payment\VipUpgraded  $event
     * @return void
     */
    public function handle(VipUpgraded $event)
    {
        $event->user->notify(
            new \App\Notifications\Payment\VipUpgradeNotification(
                $event->message,
                $event->actionUrl,
                $event->actionText
            )
        );
    }
}
