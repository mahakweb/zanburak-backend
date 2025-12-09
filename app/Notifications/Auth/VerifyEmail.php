<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmail extends Notification
{
    use Queueable;
    private $verificationUrl;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($verificationUrl)
    {
        $this->verificationUrl = $verificationUrl;
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
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->subject('تأیید آدرس ایمیل')
            ->line('لطفا روی دکمه زیر کلیک کنید تا آدرس ایمیل خود را تأیید کنید.')
            ->action('تأیید آدرس ایمیل', $this->verificationUrl)
            ->line('اگر حساب کاربری ایجاد نکردید، هیچ اقدام دیگری لازم نیست.');
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

