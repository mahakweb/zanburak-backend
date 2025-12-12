<?php

namespace App\Listeners\Mission\CourseCompletions;

use App\Events\Mission\CourseCompletionEvent;

class SpecialCourseListener
{
    protected $missionId = 'special-course';

    /**
     * Handle the event.
     * ماموریت تکمیل دوره‌های ویژه: کاربری که دوره‌هایی با موضوعات خاص یا پیشرفته را به پایان رسانده است.
     * (دوره‌های با level_id بالا یا status خاص)
     *
     * @param  CourseCompletionEvent  $event
     * @return void
     */
    public function handle(CourseCompletionEvent $event)
    {
        $course = $event->course;
        
        // بررسی اینکه آیا دوره ویژه است (مثلاً level پیشرفته)
        // می‌توانید منطق خود را بر اساس level_id یا status_id تنظیم کنید
        $isSpecialCourse = $course->level_id && $course->level_id >= 3; // فرض: level_id >= 3 یعنی پیشرفته

        if ($isSpecialCourse) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
