<?php

namespace App\Notifications;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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
    protected $ip;
    protected $browser;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        $this->notificationService = new NotificationService();
        // ذخیره IP و Browser در constructor چون در queue ممکن است request در دسترس نباشد
        try {
            $this->ip = request()->ip() ?? null;
            $this->browser = request()->header('User-Agent') ?? null;
        } catch (\Exception $e) {
            // اگر request در دسترس نباشد (مثلاً در queue)
            $this->ip = null;
            $this->browser = null;
        }
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
            Log::warning("BaseNotification: Event slug not defined for " . get_class($this));
            return ['database'];
        }

        // دریافت کانال‌های فعال از NotificationService
        $channels = $this->notificationService->getChannels($notifiable, $eventSlug);

        Log::info("BaseNotification: Channels for event '{$eventSlug}' and user {$notifiable->id}", [
            'channels' => $channels,
            'notification_class' => get_class($this)
        ]);

        // اگر هیچ کانالی فعال نبود، حداقل database را برمی‌گردانیم
        if (empty($channels)) {
            Log::warning("BaseNotification: No channels found for event '{$eventSlug}', using database only", [
                'user_id' => $notifiable->id,
                'notification_class' => get_class($this)
            ]);
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


