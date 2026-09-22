<?php

namespace App\Listeners\Score\Course;

use App\Events\Course\CourseCompleted;

class CourseCompletedListener
{
    /**
     * امتیاز تکمیل دوره از ماموریت course-completion
     * (از طریق CourseCompletionEvent / Mission listener) اعطا می‌شود.
     * این listener دیگر امتیاز جداگانه نمی‌دهد تا دوباره‌کاری نشود.
     */
    public function handle(CourseCompleted $event)
    {
        // no-op: mission system handles scoring via course-completion
    }
}
