<?php

use App\Services\NotificationService;
use App\Notifications\Channels\SyncDatabaseChannel;
use Illuminate\Contracts\Queue\ShouldQueue;

if (!function_exists('sendNotification')) {
    /**
     * Helper function برای ارسال اطلاع‌رسانی
     *
     * @param \App\Models\User $user
     * @param string $eventSlug
     * @param array $data
     * @return void
     */
    function sendNotification($user, string $eventSlug, array $data = [])
    {
        $service = new NotificationService();
        $service->sendNotification($user, $eventSlug, $data);
    }
}

if (!function_exists('sendBulkNotification')) {
    /**
     * Helper function برای ارسال اطلاع‌رسانی به چند کاربر
     *
     * @param array $userIds
     * @param string $eventSlug
     * @param array $data
     * @return void
     */
    function sendBulkNotification(array $userIds, string $eventSlug, array $data = [])
    {
        $service = new NotificationService();
        $service->sendBulkNotification($userIds, $eventSlug, $data);
    }
}

if (!function_exists('notifyWithImmediateDatabase')) {
    /**
     * ارسال notification با ثبت فوری دیتابیس
     * 
     * این تابع نوتیفیکیشن‌های دیتابیس را فوراً ثبت می‌کند
     * و ایمیل و SMS را در صف قرار می‌دهد.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     * @return void
     */
    function notifyWithImmediateDatabase($notifiable, $notification)
    {
        // اگر notification از ShouldQueue استفاده نمی‌کند، به صورت عادی ارسال می‌کنیم
        if (!$notification instanceof ShouldQueue) {
            $notifiable->notify($notification);
            return;
        }
        
        // دریافت کانال‌های notification
        $channels = $notification->via($notifiable);
        
        // جدا کردن کانال دیتابیس از بقیه
        $databaseChannels = [];
        $otherChannels = [];
        
        foreach ($channels as $channel) {
            if ($channel === SyncDatabaseChannel::class || $channel === 'database') {
                $databaseChannels[] = SyncDatabaseChannel::class;
            } else {
                $otherChannels[] = $channel;
            }
        }
        
        // ارسال فوری نوتیفیکیشن‌های دیتابیس
        if (!empty($databaseChannels)) {
            $notifiable->sendNow($notification, $databaseChannels);
        }
        
        // اگر کانال‌های دیگری وجود داشت، notification را با آن کانال‌ها در صف قرار می‌دهیم
        if (!empty($otherChannels)) {
            // ایجاد یک notification جدید با فقط کانال‌های غیر دیتابیس
            $queuedNotification = clone $notification;
            
            // Override کردن via برای برگرداندن فقط کانال‌های غیر دیتابیس
            $originalVia = $queuedNotification->via($notifiable);
            $queuedNotification->via = function($notifiable) use ($otherChannels) {
                return $otherChannels;
            };
            
            $notifiable->notify($queuedNotification);
        }
    }
}

