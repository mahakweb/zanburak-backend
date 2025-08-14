<?php

namespace App\Listeners\User;

use App\Events\User\ChangeMobile;
use App\Notifications\User\ChangeMobileNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ChangeMobileListener
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
     * @param  \App\Events\ChangeMobile  $event
     * @return void
     */
    public function handle(ChangeMobile $event)
    {
        $event->user->notify(new ChangeMobileNotification());
    }
}
