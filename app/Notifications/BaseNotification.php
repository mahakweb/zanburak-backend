<?php

namespace App\Notifications;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Base Notification Class
 * 
 * این کلاس پایه برای تمام Notification های فیزیکی است.
 * از NotificationService برای دریافت کانال‌های فعال استفاده می‌کند.
 * 
 * نحوه استفاده:
 * 1. Notification فیزیکی از این کلاس ارث‌بری می‌کند
 * 2. متد getEventSlug() را override می‌کند و slug رویداد را برمی‌گرداند
 * 3. متدهای toMail(), toSms(), toArray() را override می‌کند برای محتوای خاص
 */
abstract class BaseNotification extends Notification
{
    use Queueable;

    protected $notificationService;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    /**
     * Get the notification's delivery channels.
     * این متد از NotificationService برای دریافت کانال‌های فعال استفاده می‌کند.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $eventSlug = $this->getEventSlug();
        
        if (!$eventSlug) {
            // اگر event slug تعریف نشده باشد، فقط database را برمی‌گردانیم
            return ['database'];
        }

        // دریافت کانال‌های فعال از NotificationService
        $channels = $this->notificationService->getChannels($notifiable, $eventSlug);

        // اگر هیچ کانالی فعال نبود، حداقل database را برمی‌گردانیم
        if (empty($channels)) {
            return ['database'];
        }

        return $channels;
    }

    /**
     * Get the event slug for this notification.
     * این متد باید در کلاس‌های فرزند override شود.
     *
     * @return string|null
     */
    abstract protected function getEventSlug(): ?string;
}

