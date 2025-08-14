<?php

namespace App\Listeners\Course;

use App\Events\Course\GetCourse;
use App\Notifications\Course\GetCourseNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class GetCourseListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\GetCourse  $event
     * @return void
     */
    public function handle(GetCourse $event)
    {
        $event->user->notify(new GetCourseNotification($event->courses));
    }
}
