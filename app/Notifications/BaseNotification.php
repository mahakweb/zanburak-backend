<?php

namespace App\Notifications;

use App\Services\NotificationService;
use App\Notifications\Channels\SyncDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
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
     * اگر notification از ShouldQueue استفاده کند، کانال‌های دیتابیس را فوراً ارسال می‌کند
     * و فقط کانال‌های دیگر (ایمیل، SMS) را برمی‌گرداند تا در صف قرار گیرند.
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
            $channels = [SyncDatabaseChannel::class];
        } else {
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
                $channels = [SyncDatabaseChannel::class];
            }
        }

        // اگر notification از ShouldQueue استفاده می‌کند، کانال‌های دیتابیس را فوراً ارسال می‌کنیم
        if ($this instanceof ShouldQueue) {
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
                try {
                    // ایجاد یک notification wrapper که از ShouldQueue استفاده نمی‌کند
                    $databaseNotification = new class($this) extends Notification {
                        protected $originalNotification;
                        
                        public function __construct($originalNotification) {
                            $this->originalNotification = $originalNotification;
                        }
                        
                        public function via($notifiable) {
                            return [SyncDatabaseChannel::class];
                        }
                        
                        public function toArray($notifiable) {
                            return $this->originalNotification->toArray($notifiable);
                        }
                    };
                    
                    // ارسال فوری بدون queue
                    NotificationFacade::sendNow($notifiable, $databaseNotification);
                    
                    Log::info("BaseNotification: Database notification sent immediately", [
                        'user_id' => $notifiable->id ?? null,
                        'notification_class' => get_class($this),
                        'channels' => $databaseChannels
                    ]);
                } catch (\Exception $e) {
                    Log::error("BaseNotification: Error sending database notification immediately: " . $e->getMessage(), [
                        'user_id' => $notifiable->id ?? null,
                        'notification_class' => get_class($this),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            // فقط کانال‌های غیر دیتابیس را برمی‌گردانیم تا در صف قرار گیرند
            return $otherChannels;
        }

        // اگر ShouldQueue نیست، همه کانال‌ها را برمی‌گردانیم
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


