<?php

namespace App\Notifications\Report;

use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ReportRejectedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $reportTitle;
    public $reason;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $reportTitle, string $reason = null)
    {
        parent::__construct();
        $this->reportTitle = $reportTitle;
        $this->reason = $reason;
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'report-rejected';
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
            ->subject('رد گزارش')
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line("گزارش شما «{$this->reportTitle}» رد شد.");
        
        if ($this->reason) {
            $mail->line("دلیل: {$this->reason}");
        }
        
        $mail->line('در صورت نیاز می‌توانید گزارش جدیدی ارسال کنید.')
            ->action('ارسال گزارش جدید', frontendUrl('panel/reports'))
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
        // الگو در پنل ملی پیامک: ReportRejectedNotification یا ReportRejectedNotificationWithReason
        if ($this->reason) {
            // ReportRejectedNotificationWithReason
            return [
                'params' => [
                    $this->reportTitle, // {0}
                    $this->reason, // {1}
                ],
                'body_id' => null, // بعداً جایگزین می‌شود
                'phone' => $notifiable->mobile,
            ];
        }
        
        // ReportRejectedNotification
        return [
            'params' => [
                $this->reportTitle, // {0}
            ],
            'body_id' => null, // بعداً جایگزین می‌شود
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
        $message = 'گزارش شما <span class="text-primary">' . $this->reportTitle . '</span> رد شد.';
        
        if ($this->reason) {
            $message .= " <span class=\"text-warning\">دلیل: {$this->reason}</span>";
        }

        return [
            'name' => $notifiable->first_name . ' ' . $notifiable->last_name,
            'email' => $notifiable->email,
            'icon-class' => 'warning',
            'icon' => '<svg width="18" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" fill="currentColor"></path></svg>',
            'message' => $message,
            'ip' => $this->ip ?? null,
            'browser' => $this->browser ?? null,
        ];
    }
}


