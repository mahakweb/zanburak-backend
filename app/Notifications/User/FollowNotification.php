<?php

namespace App\Notifications\User;

use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class FollowNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $follower;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($follower = null)
    {
        parent::__construct();
        $this->follower = $follower ?? auth()->user() ?? auth('api')->user();
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'new-follower';
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $followerName = $this->follower ? ($this->follower->first_name . ' ' . $this->follower->last_name) : 'کسی';
        
        return (new MailMessage)
            ->subject('دنبال‌کننده جدید')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line("{$followerName} شما را دنبال کرد.")
            ->action('مشاهده پروفایل', url("/profile/" . ($this->follower->username ?? '')))
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
            'message' => "شما یک دنبال‌کننده جدید دارید. زنبورک",
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
            'name'       => $notifiable->first_name.' '.$notifiable->last_name,
            'email'      => $notifiable->email,
            'icon-class' => 'yellow',
            'icon'       => '<svg height="18" width="18" version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="currentColor"><path class="" d="M458.159,404.216c-18.93-33.65-49.934-71.764-100.409-93.431c-28.868,20.196-63.938,32.087-101.745,32.087 c-37.828,0-72.898-11.89-101.767-32.087c-50.474,21.667-81.479,59.782-100.398,93.431C28.731,448.848,48.417,512,91.842,512 c43.426,0,164.164,0,164.164,0s120.726,0,164.153,0C463.583,512,483.269,448.848,458.159,404.216z"></path> <path class="" d="M256.005,300.641c74.144,0,134.231-60.108,134.231-134.242v-32.158C390.236,60.108,330.149,0,256.005,0 c-74.155,0-134.252,60.108-134.252,134.242V166.4C121.753,240.533,181.851,300.641,256.005,300.641z"></path></svg>',
            'message'    => '<span class="span text-primary font-bold">'.($this->follower ? ($this->follower->first_name.' '.$this->follower->last_name) : 'کسی').'</span> شروع به دنبال کردن شما کرده است.',
            'ip'         => $this->ip ?? null,
            'browser'    => $this->browser ?? null,
        ];
    }
}

