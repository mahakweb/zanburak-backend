<?php

namespace App\Listeners\Mission\CourseCompletions;

use App\Events\Mission\CourseCompletionEvent;
use Illuminate\Support\Facades\DB;

class FastestCompletionListener
{
    protected $missionId = 'fastest-completion';

    /**
     * Handle the event.
     * ماموریت سریع‌ترین تکمیل‌کننده: کاربری که یک دوره را در کمترین زمان ممکن پس از ثبت‌نام به اتمام رسانده است.
     *
     * @param  CourseCompletionEvent  $event
     * @return void
     */
    public function handle(CourseCompletionEvent $event)
    {
        $pivot = $event->user->courses()
            ->where('course_id', $event->course->id)
            ->first();

        if (!$pivot || !$pivot->pivot->completed_at || !$pivot->pivot->created_at) {
            return;
        }

        $completionDuration = elapsed_hours($pivot->pivot->created_at, $pivot->pivot->completed_at);

        $fastestCompletion = DB::table('course_user')
            ->where('course_id', $event->course->id)
            ->whereNotNull('completed_at')
            ->get(['created_at', 'completed_at'])
            ->map(fn ($row) => elapsed_hours($row->created_at, $row->completed_at))
            ->min();

        if ($fastestCompletion !== null && $completionDuration <= $fastestCompletion) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
