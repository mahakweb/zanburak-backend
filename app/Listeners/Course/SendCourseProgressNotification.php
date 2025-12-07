<?php

namespace App\Listeners\Course;

use App\Events\Course\CourseProgress;

class SendCourseProgressNotification
{
    public function handle(CourseProgress $event)
    {
        $event->user->notify(
            new \App\Notifications\Course\CourseProgressNotification(
                $event->course,
                $event->progress
            )
        );
    }
}
