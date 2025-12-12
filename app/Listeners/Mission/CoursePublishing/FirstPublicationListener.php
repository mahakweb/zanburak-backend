<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;

class FirstPublicationListener
{
    protected $missionId = 'first-publication';

    /**
     * Handle the event.
     * ماموریت اولین انتشار: مدرسی که اولین دوره آموزشی خود را در سایت منتشر می‌کند.
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent $event)
    {
        $publishedCoursesCount = $event->user->addCourse()
            ->where('publish', true)
            ->count();

        if ($publishedCoursesCount == 1) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
