<?php

namespace App\Notifications\User;

use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LoginNotification extends BaseNotification implements ShouldQueue
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
        return 'login';
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
            ->subject('ورود به سایت')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line('ورود شما به حساب کاربری با موفقیت انجام شد.')
            ->line('اگر این کار توسط شما انجام نشده است، لطفاً فوراً رمز عبور خود را تغییر دهید.')
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
        return [
            'message' => "ورود شما به حساب کاربری انجام شد. اگر این کار توسط شما انجام نشده است، لطفاً فوراً رمز عبور خود را تغییر دهید. زنبورک",
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
            'icon-class' => 'danger',
            'icon' => '<svg width="18" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.887 21.98c.076.026.15.027.226 0C13.084 21.65 20 19.018 20 11.253V4.304a.4.4 0 0 0-.303-.389l-7.6-1.903a.4.4 0 0 0-.194 0l-7.6 1.903A.4.4 0 0 0 4 4.304v6.948c0 7.687 6.918 10.387 7.887 10.728ZM13 7a1 1 0 1 0-2 0v5a1 1 0 1 0 2 0V7Zm-1 7a1 1 0 1 0 0 2h.008a1 1 0 1 0 0-2H12Z" fill="currentColor"></path></svg>',
            'message' => ' گزارش ورود به سیستم با آدرس آی پی  <span class="font-bold">' . ($this->ip ?? 'نامشخص') . '</span>  ثبت شده است. در صورتی که فکر میکنید این کار توسط شما انجام نشده هر چه سریع تر به قسمت  مدیریت نشست‌ها در بخش پنل کاربری خود در زنبورک مراجعه بفرمایید و نشست مورد نظر را حذف کنید.',
            'ip' => $this->ip ?? null,
            'browser'    => $this->browser ?? null,
        ];
    }
}

