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
        $event->user->notify(new LoginNotification());
        
        // ارسال اطلاع‌رسانی از طریق سیستم جدید
        sendNotification($event->user, 'login', [
            'message' => "ورود شما به حساب کاربری با موفقیت انجام شد.",
            'subject' => 'ورود به سایت',
            'action_url' => frontendUrl('panel'),
            'action_text' => 'ورود به پنل',
            'sms_message' => "ورود شما به حساب کاربری انجام شد.",
        ]);
    }
}
