<?php

namespace App\Listeners\Course;

use App\Events\Course\CourseUpdated;

class SendCourseUpdatedNotification
{
    public function handle(CourseUpdated $event)
    {
        $event->user->notify(
            new \App\Notifications\Course\CourseUpdatedNotification($event->course)
        );
    }
}
