<?php

namespace App\Notifications\User;

use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AccountSuspendedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $reason;
    public $suspendedUntil;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $reason, $suspendedUntil = null)
    {
        parent::__construct();
        $this->reason = $reason;
        $this->suspendedUntil = $suspendedUntil;
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'account-suspended';
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('مسدود شدن حساب کاربری')
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line('حساب کاربری شما مسدود شد.');
        
        $mail->line("دلیل: {$this->reason}");
        
        if ($this->suspendedUntil) {
            $mail->line("مسدودیت تا تاریخ: " . jdate($this->suspendedUntil)->format('Y/m/d H:i'));
        }
        
        $mail->line('در صورت نیاز می‌توانید با پشتیبانی تماس بگیرید.')
            ->action('تماس با پشتیبانی', frontendUrl('contact'))
            ->line('با تشکر از شما');

        return $mail;
    }

    /**
     * Get the SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toSms($notifiable)
    {
        // الگو در پنل ملی پیامک: AccountSuspendedNotification یا AccountSuspendedNotificationWithDate
        if ($this->suspendedUntil) {
            // AccountSuspendedNotificationWithDate
            return [
                'params' => [
                    $this->reason, // {0}
                    jdate($this->suspendedUntil)->format('Y/m/d H:i'), // {1}
                ],
                'body_id' => 405111, // AccountSuspendedNotificationWithDate - از SMS_TEMPLATES_ALL_NOTIFICATIONS.txt
                'phone' => $notifiable->mobile,
            ];
        }
        
        // AccountSuspendedNotification
        return [
            'params' => [
                $this->reason, // {0}
            ],
            'body_id' => 405110, // AccountSuspendedNotification - از SMS_TEMPLATES_ALL_NOTIFICATIONS.txt
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
        $message = 'حساب کاربری شما مسدود شد. <span class="text-warning font-bold">دلیل: ' . $this->reason . '</span>';
        
        if ($this->suspendedUntil) {
            $message .= " <span class=\"text-muted\">(مسدودیت تا: " . jdate($this->suspendedUntil)->format('Y/m/d H:i') . ")</span>";
        }

        return [
            'name' => $notifiable->first_name . ' ' . $notifiable->last_name,
            'email' => $notifiable->email,
            'icon-class' => 'error',
            'icon' => '<svg width="18" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" fill="currentColor"></path></svg>',
            'message' => $message,
            'ip' => $this->ip ?? null,
            'browser' => $this->browser ?? null,
        ];
    }
}


