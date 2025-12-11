<?php

namespace App\Notifications\Course;

use App\Models\Course;
use App\Models\Certificate;
use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CertificateIssuedNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $course;
    public $certificate;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Course $course, Certificate $certificate)
    {
        parent::__construct();
        $this->course = $course;
        $this->certificate = $certificate;
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'certificate-issued';
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
            ->subject('گواهینامه شما آماده است')
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line("گواهینامه شما برای دوره «{$this->course->title}» آماده است.")
            ->line('می‌توانید گواهینامه خود را دانلود کنید.')
            ->action('دانلود گواهینامه', frontendUrl("certificate/{$this->certificate->uuid}"))
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
        // الگو در پنل ملی پیامک: CertificateIssuedNotification
        // متغیرها: {0} = عنوان دوره
        return [
            'params' => [
                $this->course->title, // {0}
            ],
            'body_id' => 405122, // CertificateIssuedNotification - از SMS_TEMPLATES_ALL_NOTIFICATIONS.txt
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
            'icon-class' => 'success',
            'icon' => '<svg width="18" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM10 17L5 12L6.41 10.59L10 14.17L17.59 6.58L19 8L10 17Z" fill="currentColor"></path></svg>',
            'message' => 'گواهینامه شما برای دوره <span class="text-primary">' . $this->course->title . '</span> آماده است.',
            'ip' => $this->ip ?? null,
            'browser' => $this->browser ?? null,
        ];
    }
}


