<?php

namespace App\Notifications\Security;

use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class LoginFromNewDeviceNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $ip;
    public $browser;
    public $device;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $ip, string $browser, string $device = null)
    {
        parent::__construct();
        $this->ip = $ip;
        $this->browser = $browser;
        $this->device = $device;
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'login-from-new-device';
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $deviceInfo = $this->device ? "دستگاه: {$this->device}" : '';
        
        return (new MailMessage)
            ->subject('ورود از دستگاه جدید')
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line('ورود از دستگاه جدید:')
            ->line("IP: {$this->ip}")
            ->line("مرورگر: {$this->browser}")
            ->when($deviceInfo, function ($mail) use ($deviceInfo) {
                return $mail->line($deviceInfo);
            })
            ->line('در صورتی که این کار توسط شما انجام نشده است، لطفاً فوراً رمز عبور خود را تغییر دهید.')
            ->action('تغییر رمز عبور', frontendUrl('panel/change-password'))
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
        // الگو در پنل ملی پیامک: LoginFromNewDeviceNotification یا LoginFromNewDeviceNotificationWithDevice
        if ($this->device) {
            // LoginFromNewDeviceNotificationWithDevice
            return [
                'params' => [
                    $this->ip, // {0}
                    $this->browser, // {1}
                    $this->device, // {2}
                ],
                'body_id' => 405113, // LoginFromNewDeviceNotificationWithDevice - از SMS_TEMPLATES_ALL_NOTIFICATIONS.txt
                'phone' => $notifiable->mobile,
            ];
        }
        
        // LoginFromNewDeviceNotification
        return [
            'params' => [
                $this->ip, // {0}
                $this->browser, // {1}
            ],
            'body_id' => 405114, // LoginFromNewDeviceNotification - از SMS_TEMPLATES_ALL_NOTIFICATIONS.txt
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
            'icon-class' => 'warning',
            'icon' => '<svg width="18" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" fill="currentColor"></path></svg>',
            'message' => 'ورود از دستگاه جدید: <span class="text-primary">IP: ' . $this->ip . '</span> | <span class="text-primary">مرورگر: ' . $this->browser . '</span>',
            'ip' => $this->ip,
            'browser' => $this->browser,
        ];
    }
}


