<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TopTeacherListener
{
    protected $missionId = 'top-teacher';

    /**
     * Handle the event.
     * ماموریت مدرس محبوب: مدرسی که دوره‌ای با حداقل 100 خرید را در هفته اول انتشار دوره داشته باشد. (سطح دارد)
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent $event)
    {
        $course = $event->course;
        $firstWeekEnd = $event->publishedAt->copy()->addWeek();

        // تعداد خریدها در هفته اول
        $purchasesInFirstWeek = $course->users()
            ->wherePivot('created_at', '>=', $event->publishedAt)
            ->wherePivot('created_at', '<=', $firstWeekEnd)
            ->count();

        if ($purchasesInFirstWeek >= 100) {
            // تعداد دوره‌هایی که این مدرس با 100+ خرید در هفته اول داشته
            $popularCoursesCount = $event->user->addCourse()
                ->where('publish', true)
                ->get()
                ->filter(function($c) {
                    $firstWeekEnd = $c->created_at->copy()->addWeek();
                    return $c->users()
                        ->wherePivot('created_at', '>=', $c->created_at)
                        ->wherePivot('created_at', '<=', $firstWeekEnd)
                        ->count() >= 100;
                })
                ->count();

            upgrade_mission_for_user($event->user->id, $this->missionId, $popularCoursesCount, 1);
        }
    }
}
