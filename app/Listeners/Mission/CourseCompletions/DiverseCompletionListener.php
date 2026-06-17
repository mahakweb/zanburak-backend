<?php

namespace App\Listeners\Mission\CourseCompletions;

use App\Events\Mission\CourseCompletionEvent;
use Illuminate\Support\Facades\DB;

class DiverseCompletionListener
{
    protected $missionId = 'diverse-completion';

    /**
     * Handle the event.
     * ماموریت یادگیرنده همه‌جانبه: کاربری که 5 دوره در حداقل 2 حوزه مختلف را با موفقیت گذرانده است.
     *
     * @param  CourseCompletionEvent  $event
     * @return void
     */
    public function handle(CourseCompletionEvent $event)
    {
        // تعداد دسته‌بندی‌های مختلف که کاربر از هر کدام حداقل 1 دوره تکمیل کرده
        $diverseCategoriesCount = DB::table('course_user')
            ->join('category_course', 'course_user.course_id', '=', 'category_course.course_id')
            ->where('course_user.user_id', $event->user->id)
            ->whereNotNull('course_user.completed_at')
            ->select('category_course.category_id', DB::raw('COUNT(DISTINCT course_user.course_id) as course_count'))
            ->groupBy('category_course.category_id')
            ->havingRaw('COUNT(DISTINCT course_user.course_id) >= ?', [1])
            ->count();

        // تعداد کل دوره‌های تکمیل شده
        $totalCompleted = $event->user->courses()
            ->wherePivotNotNull('completed_at')
            ->count();

        if ($totalCompleted >= 5 && $diverseCategoriesCount >= 2) {
            upgrade_mission_for_user($event->user->id, $this->missionId, min($diverseCategoriesCount, 5), 1);
        }
    }
}
