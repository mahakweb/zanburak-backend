<?php

namespace App\Listeners\Course;

use App\Events\Course\NewCourse;

class SendNewCourseNotification
{
    public function handle(NewCourse $event)
    {
        $event->user->notify(
            new \App\Notifications\Course\NewCourseNotification($event->course)
        );
    }
}
