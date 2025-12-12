<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;

class GoldenFeedbackListener
{
    protected $missionId = 'golden-feedback';

    /**
     * Handle the event.
     * ماموریت بازخورد طلایی: مدرسی که برای 5 دوره، ماموریت بازخورد مثبت دریافت کرده است.
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent $event)
    {
        // تعداد دوره‌هایی که بازخورد مثبت دارند (بیش از 1000 لایک یا ریتینگ بالا)
        $coursesWithPositiveFeedback = $event->user->addCourse()
            ->where('publish', true)
            ->get()
            ->filter(function($course) {
                $likesCount = $course->likes()->where('type', 'like')->count();
                $avgRating = $course->ratings()->avg('rating') ?? 0;
                return $likesCount >= 1000 || $avgRating >= 4.5;
            })
            ->count();

        if ($coursesWithPositiveFeedback >= 5) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
