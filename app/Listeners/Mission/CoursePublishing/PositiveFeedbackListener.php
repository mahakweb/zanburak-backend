<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;

class PositiveFeedbackListener
{
    protected $missionId = 'positive-feedback';

    /**
     * Handle the event.
     * ماموریت بازخورد مثبت: مدرسی که برای یک دوره حداقل 1000 بازخورد مثبت و امتیاز بالا را از کاربران دریافت کرده است.
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent $event)
    {
        $course = $event->course;
        
        $likesCount = $course->likes()->where('type', 'like')->count();
        $avgRating = $course->ratings()->avg('rating') ?? 0;

        if ($likesCount >= 1000 || $avgRating >= 4.5) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
