<?php

namespace App\Notifications\Course;

use App\Models\Course;
use App\Notifications\BaseNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CourseNearCompletionNotification extends BaseNotification implements ShouldQueue
{
    use Queueable;

    public $course;
    public $remainingPercent;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Course $course, int $remainingPercent)
    {
        parent::__construct();
        $this->course = $course;
        $this->remainingPercent = $remainingPercent;
    }

    /**
     * Get the event slug for this notification.
     *
     * @return string
     */
    protected function getEventSlug(): ?string
    {
        return 'course-near-completion';
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
            ->subject('نزدیک به تکمیل دوره')
            ->from('noreply@zanburak.ir', 'zanburak | زنبورک')
            ->greeting('سلام ' . ($notifiable->first_name ?? 'کاربر') . ' عزیز!')
            ->line("فقط {$this->remainingPercent}% باقی مانده! شما نزدیک به تکمیل دوره «{$this->course->title}» هستید.")
            ->line('ادامه بدهید تا گواهینامه خود را دریافت کنید.')
            ->action('ادامه تماشا', frontendUrl("course/{$this->course->slug}"))
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
        // الگو در پنل ملی پیامک: CourseNearCompletionNotification
        // متغیرها: {0} = درصد باقی‌مانده, {1} = عنوان دوره
        return [
            'params' => [
                $this->remainingPercent, // {0}
                $this->course->title, // {1}
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
            'message' => "فقط <span class=\"text-primary font-bold\">{$this->remainingPercent}%</span> باقی مانده! شما نزدیک به تکمیل دوره <span class=\"text-primary\">{$this->course->title}</span> هستید.",
            'ip' => $this->ip ?? null,
            'browser' => $this->browser ?? null,
        ];
    }
}


