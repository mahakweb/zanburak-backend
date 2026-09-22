<?php

namespace App\Listeners\Mission\CourseCompletions;

use App\Events\Mission\CourseCompletionEvent;

class InteractiveCourseListener
{
    protected $missionId = 'interactive-course';

    /**
     * Handle the event.
     * ماموریت دوره‌های تعاملی: کاربری که دوره‌هایی با بالاترین سطح تعامل را به اتمام رسانده است.
     * (دوره‌هایی که دارای کامنت‌ها، سوالات، یا پروژه‌های عملی هستند)
     *
     * @param  CourseCompletionEvent  $event
     * @return void
     */
    public function handle(CourseCompletionEvent $event)
    {
        $course = $event->course;
        
        // بررسی سطح تعامل دوره
        $commentsCount = $course->comments()->count();
        $hasProjects = $course->section()->where(function ($q) {
            $q->whereNotNull('attached_file')
                ->where('attached_file', '!=', '')
                ->orWhereHas('episode.attachs');
        })->exists();

        // اگر دوره دارای تعامل بالا باشد (بیش از 50 کامنت یا دارای پروژه)
        $isInteractive = $commentsCount > 50 || $hasProjects;

        if ($isInteractive) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
