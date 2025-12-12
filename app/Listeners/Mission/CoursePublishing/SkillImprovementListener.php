<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;
use Illuminate\Support\Facades\DB;

class SkillImprovementListener
{
    protected $missionId = 'skill-improvement';

    /**
     * Handle the event.
     * ماموریت ارتقای مهارت: مدرسی که دوره‌های آموزشی در چندین حوزه تخصصی مختلف منتشر کرده است.
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent $event)
    {
        // تعداد دسته‌بندی‌های مختلف که مدرس در آن‌ها دوره منتشر کرده
        $diverseCategoriesCount = DB::table('courses')
            ->join('category_course', 'courses.id', '=', 'category_course.course_id')
            ->where('courses.teacher_id', $event->user->id)
            ->where('courses.publish', true)
            ->distinct()
            ->count('category_course.category_id');

        upgrade_mission_for_user($event->user->id, $this->missionId, $diverseCategoriesCount, 1);
    }
}
