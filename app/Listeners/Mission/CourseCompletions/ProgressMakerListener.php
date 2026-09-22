<?php

namespace App\Listeners\Mission\CourseCompletions;

use App\Events\Mission\CourseCompletionEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProgressMakerListener
{
    protected $missionId = 'progress-maker';

    /**
     * کاربری که در این ماه بیشتر از ماه قبل دوره تمام کرده است.
     */
    public function handle(CourseCompletionEvent $event)
    {
        $thisMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();

        $thisMonth = DB::table('course_user')
            ->where('user_id', $event->user->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $thisMonthStart)
            ->count();

        $lastMonth = DB::table('course_user')
            ->where('user_id', $event->user->id)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $lastMonthStart)
            ->where('completed_at', '<', $thisMonthStart)
            ->count();

        if ($thisMonth >= 1 && $thisMonth > $lastMonth) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
