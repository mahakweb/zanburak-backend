<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;
use Carbon\Carbon;

class ConstructiveFeedbackListener
{
    protected $missionId = 'constructive-feedback';

    /**
     * Handle the event.
     * ماموریت بازخورد دهنده فعال: کاربری که در یک بازه زمانی مشخص (مثلاً هر ماه) حداقل 10 انتقاد و بازخورد سازنده را ارائه کرده است.
     *
     * @param  CommunityActivityEvent  $event
     * @return void
     */
    public function handle(CommunityActivityEvent $event)
    {
        if ($event->type !== 'comment') {
            return;
        }

        $monthStart = Carbon::now()->startOfMonth();
        
        // تعداد کامنت‌های کاربر در این ماه (به عنوان بازخورد)
        $feedbackCount = $event->user->comments()
            ->where('created_at', '>=', $monthStart)
            ->where('approved', true)
            ->count();

        if ($feedbackCount >= 10) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
