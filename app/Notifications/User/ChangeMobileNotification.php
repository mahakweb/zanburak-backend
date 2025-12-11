<?php

namespace App\Notifications\User;

use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ChangeMobileNotification extends BaseNotification implements ShouldQueue
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
        return 'change-mobile';
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
            ->subject('تغییر شماره موبایل')
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line('شماره موبایل شما با موفقیت تغییر یافت.')
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
        // الگو در پنل ملی پیامک: ChangeMobileNotification
        // بدون متغیر
        return [
            'params' => [
                $notifiable->first_name . ' ' . $notifiable->last_name, // {0}
            ],
            'body_id' => 405105, // ChangeMobileNotification - از SMS_TEMPLATES_ALL_NOTIFICATIONS.txt
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
            'name'       => $notifiable->first_name.' '.$notifiable->last_name,
            'email'      => $notifiable->email,
            'icon-class' => 'info',
            'icon'       => '<svg width="18" height="20" fill="currentColor" viewBox="-4.5 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="m12.75 0h-10.5c-1.243 0-2.25 1.008-2.25 2.25v19.5c0 1.243 1.008 2.25 2.25 2.25h10.5c1.243 0 2.25-1.007 2.25-2.25v-19.5c0-1.243-1.008-2.25-2.25-2.25zm-5.25 22.5c-.827-.001-1.497-.671-1.497-1.498s.671-1.498 1.498-1.498 1.498.671 1.498 1.498c0 .414-.168.788-.439 1.059-.271.271-.646.439-1.06.439h-.001z"></path></svg>',
            'message'    => 'با تشکر، شماره موبایل شما با موفقیت تغییر یافت.',
            'ip'         => $this->ip ?? null,
            'browser'    => $this->browser ?? null,
        ];
    }
}

