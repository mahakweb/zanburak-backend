<?php

namespace App\Listeners\Mission\CourseCompletions;

use App\Support\SqlDialect;
use App\Events\Mission\CourseCompletionEvent;
use Carbon\Carbon;

class OngoingLearningListener
{
    protected $missionId = 'ongoing-learning';

    /**
     * Handle the event.
     * ماموریت یادگیری مداوم: کاربری که به طور مداوم و در طول یک سال چندین دوره را به اتمام رسانده است.
     *
     * @param  CourseCompletionEvent  $event
     * @return void
     */
    public function handle(CourseCompletionEvent $event)
    {
        $oneYearAgo = Carbon::now()->subYear();
        
        // تعداد دوره‌های تکمیل شده در یک سال گذشته
        $completedLastYear = $event->user->courses()
            ->wherePivot('completed_at', '>=', $oneYearAgo)
            ->wherePivotNotNull('completed_at')
            ->count();

        // بررسی اینکه آیا تکمیل‌ها در ماه‌های مختلف توزیع شده‌اند (حداقل 6 ماه)
        $monthsWithCompletions = $event->user->courses()
            ->wherePivot('completed_at', '>=', $oneYearAgo)
            ->wherePivotNotNull('completed_at')
            ->selectRaw(SqlDialect::year('completed_at').' as year, '.SqlDialect::month('completed_at').' as month')
            ->groupBy('year', 'month')
            ->get()
            ->count();

        if ($completedLastYear >= 6 && $monthsWithCompletions >= 6) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
