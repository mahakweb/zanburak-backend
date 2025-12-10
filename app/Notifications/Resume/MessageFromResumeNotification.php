<?php

namespace App\Notifications\Resume;

use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class MessageFromResumeNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $message;
    public $senderName;
    public $resumeTitle;
    public $actionUrl;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $message, string $senderName, string $resumeTitle = null, string $actionUrl = null)
    {
        parent::__construct();
        $this->message = $message;
        $this->senderName = $senderName;
        $this->resumeTitle = $resumeTitle;
        $this->actionUrl = $actionUrl;
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'message-from-resume';
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject('پیام از طرف رزومه')
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line("{$this->senderName} از طریق رزومه شما پیامی ارسال کرده است.");

        if ($this->resumeTitle) {
            $mailMessage->line("رزومه: {$this->resumeTitle}");
        }

        $mailMessage->line($this->message);

        if ($this->actionUrl) {
            $mailMessage->action('مشاهده پیام', $this->actionUrl);
        }

        $mailMessage->line('با تشکر از شما');

        return $mailMessage;
    }

    /**
     * Get the SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toSms($notifiable)
    {
        // الگو در پنل ملی پیامک: MessageFromResumeNotification یا MessageFromResumeNotificationWithTitle
        if ($this->resumeTitle) {
            // MessageFromResumeNotificationWithTitle
            return [
                'params' => [
                    $this->senderName, // {0}
                    $this->resumeTitle, // {1}
                ],
                'body_id' => null, // بعداً جایگزین می‌شود
                'phone' => $notifiable->mobile,
            ];
        }
        
        // MessageFromResumeNotification
        return [
            'params' => [
                $this->senderName, // {0}
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
        return [
            'name' => $notifiable->first_name . ' ' . $notifiable->last_name,
            'email' => $notifiable->email,
            'icon-class' => 'info',
            'icon' => '<svg height="18" width="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 2H7C4.24 2 2 4.23 2 6.98V12.96V13.96C2 16.71 4.24 18.94 7 18.94H8.5C8.77 18.94 9.13 19.12 9.3 19.34L10.8 21.33C11.46 22.21 12.54 22.21 13.2 21.33L14.7 19.34C14.89 19.09 15.19 18.94 15.5 18.94H17C19.76 18.94 22 16.71 22 13.96V6.98C22 4.23 19.76 2 17 2ZM13 13.75H7C6.59 13.75 6.25 13.41 6.25 13C6.25 12.59 6.59 12.25 7 12.25H13C13.41 12.25 13.75 12.59 13.75 13C13.75 13.41 13.41 13.75 13 13.75ZM17 8.75H7C6.59 8.75 6.25 8.41 6.25 8C6.25 7.59 6.59 7.25 7 7.25H17C17.41 7.25 17.75 7.59 17.75 8C17.75 8.41 17.41 8.75 17 8.75Z" fill="currentColor"></path></svg>',
            'message' => '<span class="text-primary">' . $this->senderName . '</span> از طریق رزومه شما پیامی ارسال کرده است.',
            'ip' => $this->ip ?? null,
            'browser' => $this->browser ?? null,
        ];
    }
}


