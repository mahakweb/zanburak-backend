<?php

namespace App\Notifications;



use App\Notifications\Channels\GhasedakChannel;
use App\Notifications\Channels\MeliPayamakChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActiveCodeNotification extends Notification
{
    use Queueable;
    public $code;
    public $phone;

    /*
     * @return void
     */
    public function __construct($code, $phone)
    {
        $this->code = $code;
        $this->phone = $phone;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        // return [GhasedakChannel::class];
        return [MeliPayamakChannel::class];
    }

    public function toGhasedakSms($notifiable){

        return[
            'text' => "کد احراز هویت شما در وبسایت زنبورک:\n {$this->code}",
            'phone' => $this->phone,
        ];

    }

    public function toMeliPayamakOtp($notifiable){

        return[
            'phone' => $this->phone,
            'code' => $this->code,
        ];

    }


}
