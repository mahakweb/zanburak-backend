<?php

namespace App\Listeners\Mission\CourseCompletions;

use App\Events\Mission\CourseCompletionEvent;
use App\Models\VideoView;

class PersistenceListener
{
    protected $missionId = 'persistence';

    /**
     * Handle the event.
     * ماموریت پشتکار: کاربری که یک دوره طولانی یا دشوار را به اتمام رسانده است.
     * (دوره‌های با بیش از 20 ساعت محتوا یا بیش از 50 اپیزود)
     *
     * @param  CourseCompletionEvent  $event
     * @return void
     */
    public function handle(CourseCompletionEvent $event)
    {
        $course = $event->course;
        
        // محاسبه تعداد اپیزودها
        $episodeCount = 0;
        foreach ($course->section as $section) {
            $episodeCount += $section->episode()->count();
        }

        // بررسی اینکه آیا دوره طولانی یا دشوار است
        $isLongCourse = $course->total_time && $course->total_time > 20 * 60; // بیش از 20 ساعت (به دقیقه)
        $isHardCourse = $episodeCount > 50;

        if ($isLongCourse || $isHardCourse) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
