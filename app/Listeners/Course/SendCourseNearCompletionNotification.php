<?php

namespace App\Listeners\Course;

use App\Events\Course\CourseNearCompletion;

class SendCourseNearCompletionNotification
{
    public function handle(CourseNearCompletion $event)
    {
        $event->user->notify(
            new \App\Notifications\Course\CourseNearCompletionNotification(
                $event->course,
                $event->remainingPercent
            )
        );
    }
}
