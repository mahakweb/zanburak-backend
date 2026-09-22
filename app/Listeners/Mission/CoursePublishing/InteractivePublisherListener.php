<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;

class InteractivePublisherListener
{
    protected $missionId = 'interactive-publisher';

    /**
     * Handle the event.
     * ماموریت دوره‌های تعاملی: مدرسی که بیشترین تعداد جلسات پرسش و پاسخ زنده (وبینار) یا فعالیت‌های عملی در دوره‌های خود را ارائه داده است.
     * (دوره‌هایی با اپیزودهای دارای فایل ضمیمه یا کامنت‌های زیاد)
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent $event)
    {
        $course = $event->course;
        
        // بررسی سطح تعامل دوره
        $hasAttachments = $course->section()->where(function ($q) {
            $q->whereNotNull('attached_file')
                ->where('attached_file', '!=', '')
                ->orWhereHas('episode.attachs');
        })->exists();
        
        $commentsCount = $course->comments()->count();
        
        $isInteractive = $hasAttachments || $commentsCount > 50;

        if ($isInteractive) {
            // تعداد دوره‌های تعاملی
            $interactiveCoursesCount = $event->user->addCourse()
                ->where('publish', true)
                ->get()
                ->filter(function($c) {
                    $hasAttachments = $c->section()->where(function ($q) {
                        $q->whereNotNull('attached_file')
                            ->where('attached_file', '!=', '')
                            ->orWhereHas('episode.attachs');
                    })->exists();
                    return $hasAttachments || $c->comments()->count() > 50;
                })
                ->count();

            sync_mission_progress_for_user($event->user->id, $this->missionId, (int) $interactiveCoursesCount);
        }
    }
}
