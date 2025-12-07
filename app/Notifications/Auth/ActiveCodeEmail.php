<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActiveCodeEmail extends Notification
{
    use Queueable;

    private int $code;

    public function __construct(int $code)
    {
        $this->code = $code;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->from('noreply@zanburak.ir', config('mail.from.name'))
            ->subject('کد ورود یکبار مصرف')
            ->line('کد یکبار مصرف شما: ' . $this->code)
            ->line('این کد تا ۵ دقیقه معتبر است.');
    }
}



