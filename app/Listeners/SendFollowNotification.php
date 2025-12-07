<?php

namespace App\Listeners;

use App\Events\FollowUser;
use App\Notifications\User\FollowNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendFollowNotification
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
     * @param  \App\Events\=FollowUser  $event
     * @return void
     */
    public function handle(FollowUser $event)
    {
        // ارسال اطلاع‌رسانی - Notification فیزیکی خودش کانال‌ها را از NotificationService می‌گیرد
        $follower = auth()->user() ?? auth('api')->user();
        if ($follower) {
            $event->follower->notify(new FollowNotification($follower));
        }
    }
}
