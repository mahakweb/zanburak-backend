<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\DatabaseChannel as LaravelDatabaseChannel;
use Illuminate\Support\Facades\Log;

/**
 * SyncDatabaseChannel
 * 
 * این کانال همیشه به صورت همگام (synchronous) اجرا می‌شود
 * حتی اگر Notification از ShouldQueue استفاده کند.
 * این باعث می‌شود که نوتیفیکیشن‌های دیتابیس فوراً ثبت شوند
 * در حالی که ایمیل و SMS در صف قرار می‌گیرند.
 */
class SyncDatabaseChannel extends LaravelDatabaseChannel
{
    /**
     * Send the given notification.
     * این متد همیشه به صورت همگام اجرا می‌شود
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function send($notifiable, Notification $notification)
    {
        // همیشه به صورت همگام اجرا می‌شود
        try {
            $result = parent::send($notifiable, $notification);
            Log::info("SyncDatabaseChannel: Notification sent successfully", [
                'user_id' => $notifiable->id ?? null,
                'notification_class' => get_class($notification),
                'has_toArray' => method_exists($notification, 'toArray')
            ]);
            return $result;
        } catch (\Exception $e) {
            Log::error("SyncDatabaseChannel: Error sending notification: " . $e->getMessage(), [
                'user_id' => $notifiable->id ?? null,
                'notification_class' => get_class($notification),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
