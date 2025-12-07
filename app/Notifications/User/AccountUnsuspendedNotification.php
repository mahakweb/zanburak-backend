<?php

namespace App\Notifications\User;

use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AccountUnsuspendedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'account-unsuspended';
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('رفع مسدودیت حساب کاربری')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line('مسدودیت حساب کاربری شما رفع شد.')
            ->line('اکنون می‌توانید از تمام امکانات سایت استفاده کنید.')
            ->action('ورود به پنل', url('/panel'))
            ->line('با تشکر از شما');
    }

    /**
     * Get the SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toSms($notifiable)
    {
        return [
            'message' => "مسدودیت حساب کاربری شما رفع شد. اکنون می‌توانید از تمام امکانات سایت استفاده کنید. زنبورک",
            'phone' => $notifiable->mobile,
            'body_id' => config('services.meliPayamak.notification_template_id', null),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'name' => $notifiable->first_name . ' ' . $notifiable->last_name,
            'email' => $notifiable->email,
            'icon-class' => 'success',
            'icon' => '<svg width="18" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7 2C4.23858 2 2 4.23858 2 7V17C2 19.7614 4.23858 22 7 22H17C19.7614 22 22 19.7614 22 17V7C22 4.23858 19.7614 2 17 2H7ZM13.3449 7.46591C12.7947 6.35104 11.2049 6.35104 10.6547 7.46591L9.8663 9.06343L8.10332 9.31961C6.87299 9.49839 6.38173 11.0103 7.272 11.8781L8.5477 13.1216L8.24655 14.8775C8.03639 16.1029 9.32253 17.0373 10.423 16.4588L11.9998 15.6298L13.5767 16.4588C14.6771 17.0373 15.9633 16.1029 15.7531 14.8775L15.4519 13.1216L16.7276 11.8781C17.6179 11.0103 17.1267 9.49839 15.8963 9.31961L14.1334 9.06343L13.3449 7.46591Z" fill="currentColor"></path></svg>',
            'message' => 'مسدودیت حساب کاربری شما رفع شد.',
            'ip' => $this->ip ?? null,
            'browser' => $this->browser ?? null,
        ];
    }
}


