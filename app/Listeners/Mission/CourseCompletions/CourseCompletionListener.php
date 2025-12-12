<?php

namespace App\Listeners\Mission\CourseCompletions;

use App\Events\Mission\CourseCompletionEvent;

class CourseCompletionListener
{
    protected $missionId = 'course-completion';

    /**
     * Handle the event.
     * ماموریت دانش‌آموخته: کاربری که حداقل 1 دوره آموزشی را با موفقیت گذرانده است. (سطح دارد)
     *
     * @param  CourseCompletionEvent  $event
     * @return void
     */
    public function handle(CourseCompletionEvent $event)
    {
        $completedCoursesCount = $event->user->courses()
            ->wherePivotNotNull('completed_at')
            ->count();

        upgrade_mission_for_user($event->user->id, $this->missionId, $completedCoursesCount, 1);
    }
}
