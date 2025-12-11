<?php

namespace App\Notifications\User;

use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EmailChangedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $oldEmail;
    public $newEmail;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $oldEmail, string $newEmail)
    {
        parent::__construct();
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'email-changed';
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
            ->subject('تغییر ایمیل')
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line('ایمیل شما با موفقیت تغییر یافت.')
            ->line("ایمیل قبلی: {$this->oldEmail}")
            ->line("ایمیل جدید: {$this->newEmail}")
            ->line('در صورتی که این کار توسط شما انجام نشده است، لطفاً فوراً با پشتیبانی تماس بگیرید.')
            ->action('ورود به پنل', frontendUrl('panel'))
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
        // الگو در پنل ملی پیامک: EmailChangedNotification
        // متغیرها: {0} = ایمیل قبلی, {1} = ایمیل جدید
        return [
            'params' => [
                $notifiable->first_name . ' ' . $notifiable->last_name, // {0}
            ],
            'body_id' => 405103, // EmailChangedNotification - از SMS_TEMPLATES_ALL_NOTIFICATIONS.txt
            'phone' => $notifiable->mobile,
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
            'icon-class' => 'yellow',
            'icon' => '<svg width="18" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13.25 12C13.9404 12 14.5 11.4404 14.5 10.75C14.5 10.0596 13.9404 9.5 13.25 9.5C12.5596 9.5 12 10.0596 12 10.75C12 11.4404 12.5596 12 13.25 12Z" fill="currentColor"></path> <path d="M16.19 2H7.81C4.17 2 2 4.17 2 7.81V16.18C2 19.83 4.17 22 7.81 22H16.18C19.82 22 21.99 19.83 21.99 16.19V7.81C22 4.17 19.83 2 16.19 2ZM15.89 13.47C14.86 14.49 13.39 14.81 12.09 14.4L11.03 15.45C10.94 15.54 10.78 15.54 10.68 15.45L9.71 14.48C9.57 14.34 9.33 14.34 9.18 14.48C9.03 14.62 9.04 14.86 9.18 15.01L10.15 15.98C10.25 16.08 10.25 16.24 10.15 16.33L9.74 16.74C9.57 16.92 9.24 17.03 9 17L7.91 16.85C7.55 16.8 7.22 16.46 7.16 16.1L7.01 15.01C6.97 14.77 7.09 14.44 7.25 14.27L9.6 11.92C9.2 10.62 9.51 9.15 10.54 8.12C12.01 6.65 14.41 6.65 15.89 8.12C17.37 9.59 17.37 11.99 15.89 13.47Z" fill="currentColor"></path></svg>',
            'message' => 'ایمیل شما با موفقیت تغییر یافت. <span class="text-primary">' . $this->oldEmail . '</span> → <span class="text-primary">' . $this->newEmail . '</span>',
            'ip' => $this->ip ?? null,
            'browser' => $this->browser ?? null,
        ];
    }
}


