<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
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
            ->from('noreply@zanburak.ir', config('mail.from.name'))
            ->subject('بازیابی رمز عبور')
            ->line('شما این ایمیل را دریافت کرده‌اید زیرا درخواست بازنشانی رمز عبور برای حساب شما ثبت شده است.')
            ->action('بازیابی رمز عبور', url(env('FRONT_APP_URL') . '/auth' . route('password.reset', ['token' => $this->token, 'email' => $notifiable->email], false)))
            ->line('اگر شما درخواست بازنشانی رمز عبور نکرده‌اید، هیچ اقدامی نیاز نیست.');
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
            //
        ];
    }
}

