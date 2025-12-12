<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;
use Illuminate\Support\Facades\DB;

class InnovativePublisherListener
{
    protected $missionId = 'innovative-publisher';

    /**
     * Handle the event.
     * ماموریت نوآور: مدرسی که اولین دوره آموزشی در یک موضوع جدید و خلاقانه را منتشر کرده است.
     * (دوره‌هایی در دسته‌بندی‌هایی که قبلاً دوره‌ای نداشته‌اند)
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent $event)
    {
        $course = $event->course;
        $courseCategories = $course->category;

        foreach ($courseCategories as $category) {
            // بررسی اینکه آیا این اولین دوره در این دسته‌بندی است
            $isFirstInCategory = $category->course()
                ->where('publish', true)
                ->where('id', '!=', $course->id)
                ->doesntExist();

            if ($isFirstInCategory) {
                upgrade_mission_for_user($event->user->id, $this->missionId);
                break;
            }
        }
    }
}
