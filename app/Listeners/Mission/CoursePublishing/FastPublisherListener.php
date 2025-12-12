<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;
use Carbon\Carbon;

class FastPublisherListener
{
    protected $missionId = 'fast-publisher';

    /**
     * Handle the event.
     * ماموریت انتشار سریع: مدرسی که در یک بازه زمانی مشخص بیشترین تعداد دوره‌های آموزشی را منتشر کرده است.
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent $event)
    {
        $monthStart = Carbon::now()->startOfMonth();
        
        $publishedThisMonth = $event->user->addCourse()
            ->where('publish', true)
            ->where('created_at', '>=', $monthStart)
            ->count();

        upgrade_mission_for_user($event->user->id, $this->missionId, $publishedThisMonth, 1);
    }
}
