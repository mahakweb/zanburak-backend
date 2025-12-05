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
        $event->follower->notify(new FollowNotification());

        // ارسال اطلاع‌رسانی از طریق سیستم جدید
        $follower = auth()->user() ?? auth('api')->user();
        if ($follower) {
            $followerName = $follower->first_name . ' ' . $follower->last_name;
            
            sendNotification($event->follower, 'new-follower', [
                'message' => "{$followerName} شما را دنبال کرد.",
                'subject' => 'دنبال‌کننده جدید',
                'action_url' => frontendUrl("profile/" . $follower->username),
                'action_text' => 'مشاهده پروفایل',
                'sms_message' => "شما یک دنبال‌کننده جدید دارید.",
            ]);
        }
    }
}
