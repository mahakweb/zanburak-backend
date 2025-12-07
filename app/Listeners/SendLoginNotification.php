<?php

namespace App\Listeners;

use App\Notifications\User\LoginNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLoginNotification
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
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        // ارسال اطلاع‌رسانی - Notification فیزیکی خودش کانال‌ها را از NotificationService می‌گیرد
        $event->user->notify(new LoginNotification());
    }
}
