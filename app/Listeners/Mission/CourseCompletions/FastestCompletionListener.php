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

        $purchaseTime = $pivot->pivot->created_at;
        $completionTime = $pivot->pivot->completed_at;
        $completionDuration = $completionTime->diffInHours($purchaseTime);

        // بررسی اینکه آیا این سریع‌ترین تکمیل برای این دوره است
        $fastestCompletion = DB::table('course_user')
            ->where('course_id', $event->course->id)
            ->whereNotNull('completed_at')
            ->selectRaw('MIN(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as min_hours')
            ->value('min_hours');

        if ($fastestCompletion && $completionDuration <= $fastestCompletion) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
