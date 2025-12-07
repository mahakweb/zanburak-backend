<?php

namespace App\Listeners\Payment;

use App\Events\Payment\PaymentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPaymentFailedNotification
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
     * @param  \App\Events\Payment\PaymentFailed  $event
     * @return void
     */
    public function handle(PaymentFailed $event)
    {
        $event->user->notify(
            new \App\Notifications\Payment\PaymentFailedNotification(
                $event->amount,
                $event->reason
            )
        );
    }
}
