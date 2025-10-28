<?php

namespace App\Notifications\Course;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GetCourseNotification extends Notification
{
    use Queueable;

    public $courses;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($courses)
    {
        $this->courses = $courses;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    // public function toMail($notifiable)
    // {
    //     return (new MailMessage)
    //                 ->from('noreply@zanburak.ir', 'اطلاع رسانی دوره‌های زنبورک')
    //                 ->subject('تاییدیه خرید دوره')
    //                 ->greeting('سلام!')
    //                 ->line('با تشکر از حسن انتخاب شما نسبت به انتخاب وب سایت زنبورک')
    //                 ->line($notifiable->first_name.' عزیز، دوره ['.$this->course->title.']('.route('course-single', $this->course->slug).') با موفقیت برای شما فعال شد.')
    //                 ->line('برای دسترسی به محتوای دوره میتونی روی لینک زیر کلیک کنی یا وارد حساب کاربری خودت بشی.')
    //                 ->action($this->course->title, url(route('course-single', $this->course->slug)))
    //                 ->line('لینک ورود به حساب کاربری زنبورک: [حساب کاربری]('.route('student-home').')')
    //                 ->line('امیدواریم به خوبی از محتوای دوره بهره مند بشی و ازش استفاده کنی.');
    // }
    public function toMail($notifiable)
    {
        $courses = $this->courses; // فرض می‌گیریم که $this->courses شامل تمامی دوره‌های خریداری شده است

        $emailContent = "با تشکر از حسن انتخاب شما نسبت به انتخاب وب سایت زنبورک\n";
        $emailContent .= $notifiable->first_name . " عزیز، دوره‌های خریداری شده شما عبارتند از (";

        foreach ($courses as $course) {
            $emailContent .= $course->title;
            if($courses->last() != $course)
                $emailContent .= "، ";
        }

        $emailContent .= ")";
        $emailContent .= " لینک ورود به حساب کاربری زنبورک: [حساب کاربری](https://zanburak.ir/panel)\n";
        $emailContent .= "امیدواریم به خوبی از محتوای دوره بهره مند بشی و ازش استفاده کنی.\n";

        return (new MailMessage)
            ->from('noreply@zanburak.ir', config('mail.from.name'))
            ->subject('تاییدیه خرید دوره')
            ->greeting('سلام!')
            ->line($emailContent);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    // public function toArray($notifiable)
    // {
    //     return [
    //         'name' => $notifiable->first_name . ' ' . $notifiable->last_name,
    //         'email' => $notifiable->email,
    //         'icon-class' => 'primary',
    //         'icon' => '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M12.5534 3.39388C12.1942 3.27414 11.8058 3.27414 11.4466 3.39388L6.15626 5.15732C5.70569 5.30752 5.33909 5.64035 5.14619 6.07436C2.83966 11.2641 5.0231 17.3501 10.1027 19.8899L11.4215 20.5493C11.7857 20.7313 12.2143 20.7313 12.5785 20.5493L13.8973 19.8899C18.9769 17.3501 21.1603 11.2641 18.8538 6.07436C18.6609 5.64035 18.2943 5.30752 17.8437 5.15732L12.5534 3.39388ZM16.0083 10.0515C16.3129 9.77077 16.3322 9.29629 16.0515 8.9917C15.7708 8.68712 15.2963 8.66777 14.9917 8.94849L11.1317 12.506L9.51928 10.9588C9.2204 10.672 8.74563 10.6818 8.45884 10.9807C8.17205 11.2796 8.18185 11.7544 8.48073 12.0411L10.602 14.0767C10.888 14.3511 11.3382 14.3556 11.6296 14.087L16.0083 10.0515Z" fill="currentColor"></path> </g></svg>',
    //         'message' => 'با تشکر از حسن انتخاب شما، خرید دوره <a href=' . route('course-single', $this->course->slug) . ' class="span text-primary">' . $this->course->title . '</a> با موفقیت انجام شد و هم اکنون می توانید بصورت کامل از این دوره استفاده کنید.',
    //         'ip' => request()->ip(),
    //     ];
    // }

    public function toArray($notifiable)
    {
        $courseNames = $this->courses->pluck('title')->implode(', '); // فرض می‌گیریم $this->courses شامل تمامی دوره‌های خریداری شده است
        $courseIds = $this->courses->pluck('id');
        return [
            'name' => $notifiable->first_name . ' ' . $notifiable->last_name,
            'first_name' => $notifiable->first_name,
            'last_name' => $notifiable->last_name,
            'email' => $notifiable->email,
            'courseIds' => $courseIds,
            'icon-class' => 'primary',
            'icon' => '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.5534 3.39388C12.1942 3.27414 11.8058 3.27414 11.4466 3.39388L6.15626 5.15732C5.70569 5.30752 5.33909 5.64035 5.14619 6.07436C2.83966 11.2641 5.0231 17.3501 10.1027 19.8899L11.4215 20.5493C11.7857 20.7313 12.2143 20.7313 12.5785 20.5493L13.8973 19.8899C18.9769 17.3501 21.1603 11.2641 18.8538 6.07436C18.6609 5.64035 18.2943 5.30752 17.8437 5.15732L12.5534 3.39388ZM16.0083 10.0515C16.3129 9.77077 16.3322 9.29629 16.0515 8.9917C15.7708 8.68712 15.2963 8.66777 14.9917 8.94849L11.1317 12.506L9.51928 10.9588C9.2204 10.672 8.74563 10.6818 8.45884 10.9807C8.17205 11.2796 8.18185 11.7544 8.48073 12.0411L10.602 14.0767C10.888 14.3511 11.3382 14.3556 11.6296 14.087L16.0083 10.0515Z" fill="currentColor"></path></svg>',
            'message' => 'با تشکر از حسن انتخاب شما، خرید دوره‌های  <strong class="font-semibold">' . $courseNames . '</strong> با موفقیت انجام شد و هم اکنون می توانید بصورت کامل از این دوره‌ها استفاده کنید.',
            'ip' => request()->ip(),
            'browser'    => request()->header('User-Agent'),
        ];
    }

}
