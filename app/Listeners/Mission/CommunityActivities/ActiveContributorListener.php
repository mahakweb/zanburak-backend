<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;
use Carbon\Carbon;

class ActiveContributorListener
{
    protected $missionId = 'active-contributor';

    /**
     * Handle the event.
     * ماموریت فعال‌ترین مشارکت‌کننده: کاربری که در یک ماه گذشته بیشترین تعداد کامنت را در دوره‌های مختلف ارسال کرده است.
     *
     * @param  CommunityActivityEvent  $event
     * @return void
     */
    public function handle(CommunityActivityEvent $event)
    {
        if ($event->type !== 'comment') {
            return;
        }

        $monthStart = Carbon::now()->subMonth();
        
        $commentsCount = $event->user->comments()
            ->where('created_at', '>=', $monthStart)
            ->count();

        upgrade_mission_for_user($event->user->id, $this->missionId, $commentsCount, 1);
    }
}
