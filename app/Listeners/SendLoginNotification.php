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
        $user = $event->user;
        $currentIp = request()->ip();
        $currentBrowser = request()->header('User-Agent');
        
        // بررسی اینکه آیا این IP قبلاً استفاده شده است یا نه
        $previousLogin = \App\Models\UserLogin::where('user_id', $user->id)
            ->where('ip_address', '!=', $currentIp)
            ->whereNotNull('ip_address')
            ->latest('logged_in_at')
            ->first();
        
        // اگر IP جدید است، notification ارسال کن
        if ($previousLogin) {
            $user->notify(new \App\Notifications\Security\LoginFromNewDeviceNotification(
                $currentIp,
                $currentBrowser,
                $previousLogin->device
            ));
        }
        
        // ارسال اطلاع‌رسانی ورود - Notification فیزیکی خودش کانال‌ها را از NotificationService می‌گیرد
        $user->notify(new LoginNotification());
        
        // Fire event for daily login points
        event(new \App\Events\Score\User\DailyLogin($user));
    }
}
