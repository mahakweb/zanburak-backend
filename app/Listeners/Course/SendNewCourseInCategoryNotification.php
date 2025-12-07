<?php

namespace App\Listeners\Course;

use App\Events\Course\NewCourseInCategory;

class SendNewCourseInCategoryNotification
{
    public function handle(NewCourseInCategory $event)
    {
        $event->user->notify(
            new \App\Notifications\Course\NewCourseInCategoryNotification($event->course, $event->category)
        );
    }
}
