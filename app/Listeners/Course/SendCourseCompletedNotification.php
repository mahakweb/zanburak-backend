<?php

namespace App\Listeners\Course;

use App\Events\Course\CourseCompleted;

class SendCourseCompletedNotification
{
    public function handle(CourseCompleted $event)
    {
        $event->user->notify(
            new \App\Notifications\Course\CourseCompletedNotification($event->course)
        );
    }
}
