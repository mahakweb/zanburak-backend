<?php

namespace App\Notifications;

use App\Models\Event;
use App\Notifications\Channels\SyncDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Log;

class CustomEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $event;
    public $data;
    public $channels;

    /**
     * Create a new notification instance.
     *
     * @param Event $event
     * @param array $data
     * @param array $channels
     */
    public function __construct(Event $event, array $data, array $channels)
    {
        $this->event = $event;
        $this->data = $data;
        $this->channels = $channels;
    }

    /**
     * Get the notification's delivery channels.
     * اگر notification از ShouldQueue استفاده کند، کانال‌های دیتابیس را فوراً ارسال می‌کند
     * و فقط کانال‌های دیگر (ایمیل، SMS) را برمی‌گرداند تا در صف قرار گیرند.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // اگر notification از ShouldQueue استفاده می‌کند، کانال‌های دیتابیس را فوراً ارسال می‌کنیم
        if ($this instanceof ShouldQueue) {
            $databaseChannels = [];
            $otherChannels = [];
            
            foreach ($this->channels as $channel) {
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
                    
                    Log::info("CustomEventNotification: Database notification sent immediately", [
                        'user_id' => $notifiable->id ?? null,
                        'event_slug' => $this->event->slug ?? null,
                        'channels' => $databaseChannels
                    ]);
                } catch (\Exception $e) {
                    Log::error("CustomEventNotification: Error sending database notification immediately: " . $e->getMessage(), [
                        'user_id' => $notifiable->id ?? null,
                        'event_slug' => $this->event->slug ?? null,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            // فقط کانال‌های غیر دیتابیس را برمی‌گردانیم تا در صف قرار گیرند
            return $otherChannels;
        }

        // اگر ShouldQueue نیست، همه کانال‌ها را برمی‌گردانیم
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->subject($this->data['subject'] ?? $this->event->title)
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line($this->data['message'] ?? $this->event->description);

        // اضافه کردن لینک در صورت وجود
        if (isset($this->data['action_url']) && isset($this->data['action_text'])) {
            $message->action($this->data['action_text'], $this->data['action_url']);
        }

        // اضافه کردن خطوط اضافی در صورت وجود
        if (isset($this->data['additional_lines']) && is_array($this->data['additional_lines'])) {
            foreach ($this->data['additional_lines'] as $line) {
                $message->line($line);
            }
        }

        return $message;
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_slug' => $this->event->slug,
            'event_icon' => $this->event->icon,
            'message' => $this->data['message'] ?? $this->event->title,
            'action_url' => $this->data['action_url'] ?? null,
            'action_text' => $this->data['action_text'] ?? null,
            'data' => $this->data,
        ];
    }

    /**
     * Get the SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toSms($notifiable)
    {
        // الگو در پنل ملی پیامک: CustomEventNotification
        // متغیرها: {0} = متن پیام (داینامیک)
        $message = $this->data['sms_message'] ?? $this->data['message'] ?? $this->event->title;
        
        // حذف HTML tags در صورت وجود
        $message = strip_tags($message);
        
        return [
            'params' => [
                $message, // {0}
            ],
            'body_id' => null, // بعداً جایگزین می‌شود
            'phone' => $notifiable->mobile,
        ];
    }
}


