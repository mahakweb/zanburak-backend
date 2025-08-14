<?php

namespace App\Notifications\User;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
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
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
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
            'message' => ' گزارش ورود به سیستم با آدرس آی پی  <span class="font-bold">' . request()->ip() . '</span>  ثبت شده است. در صورتی که فکر میکنید این کار توسط شما انجام نشده هر چه سریع تر به قسمت  مدیریت نشست‌ها در بخش پنل کاربری خود در زنبورک مراجعه بفرمایید و نشست مورد نظر را حذف کنید.',
            'ip' => request()->ip(),
            'browser'    => request()->header('User-Agent'),
        ];
    }
}
