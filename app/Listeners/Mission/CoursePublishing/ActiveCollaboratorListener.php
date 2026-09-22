<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;

class ActiveCollaboratorListener
{
    protected $missionId = 'active-collaborator';

    /**
     * Handle the event.
     * ماموریت همکار فعال: مدرسی که بیشترین تعداد همکاری یا اشتراک‌گذاری محتوا با دیگر مدرسان را در دوره‌های خود داشته است.
     * (دوره‌هایی که دارای کامنت‌ها یا مشارکت‌های دیگر مدرسان هستند)
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent $event)
    {
        $course = $event->course;
        
        // بررسی اینکه آیا دوره دارای مشارکت مدرسان دیگر است
        $hasCollaboration = $course->comments()
            ->whereHas('user', function($q) {
                $q->where('role', 'teacher');
            })
            ->exists();

        if ($hasCollaboration) {
            // تعداد دوره‌های با همکاری
            $collaborativeCoursesCount = $event->user->addCourse()
                ->where('publish', true)
                ->whereHas('comments', function($q) {
                    $q->whereHas('user', function($uq) {
                        $uq->where('role', 'teacher');
                    });
                })
                ->count();

            sync_mission_progress_for_user($event->user->id, $this->missionId, (int) $collaborativeCoursesCount);
        }
    }
}
