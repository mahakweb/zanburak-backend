<?php

namespace App\Notifications;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
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
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
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
        // این متد برای استفاده در SMS Channel استفاده می‌شود
        // متن پیامک را برمی‌گرداند
        $message = $this->data['sms_message'] ?? $this->data['message'] ?? $this->event->title;
        
        // در صورت نیاز می‌توانید الگوی پیامک را اینجا تنظیم کنید
        // برای مثال:
        // $message = "زنبورک: " . $message;
        
        return [
            'message' => $message,
            'phone' => $notifiable->mobile,
            'body_id' => $this->data['sms_template_id'] ?? config('services.meliPayamak.notification_template_id', null),
        ];
    }
}


