<?php

namespace App\Notifications\Course;

use App\Models\Course;
use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CourseReminderNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $course;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Course $course)
    {
        parent::__construct();
        $this->course = $course;
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'course-reminder';
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
            ->subject('یادآوری: ادامه تماشای دوره')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line("یادآوری: شما در حال تماشای دوره «{$this->course->title}» هستید.")
            ->line('امیدواریم به زودی به تماشای دوره ادامه دهید.')
            ->action('ادامه تماشا', url("/course/{$this->course->slug}"))
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
            'message' => "یادآوری: شما در حال تماشای دوره {$this->course->title} هستید. زنبورک",
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
            'name' => $notifiable->first_name . ' ' . $notifiable->last_name,
            'email' => $notifiable->email,
            'icon-class' => 'info',
            'icon' => '<svg height="18" width="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z" fill="currentColor"></path></svg>',
            'message' => 'یادآوری: شما در حال تماشای دوره <span class="text-primary">' . $this->course->title . '</span> هستید.',
            'ip' => $this->ip ?? null,
            'browser' => $this->browser ?? null,
        ];
    }
}


