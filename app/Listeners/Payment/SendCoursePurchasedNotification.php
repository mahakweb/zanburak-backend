<?php

namespace App\Listeners\Payment;

use App\Events\Payment\CoursePurchased;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCoursePurchasedNotification
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
     * @param  \App\Events\Payment\CoursePurchased  $event
     * @return void
     */
    public function handle(CoursePurchased $event)
    {
        $event->user->notify(
            new \App\Notifications\Payment\CoursePurchaseNotification(
                $event->message,
                $event->actionUrl,
                $event->actionText
            )
        );
    }
}
