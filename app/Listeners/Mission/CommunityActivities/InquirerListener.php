<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;
use Carbon\Carbon;

class InquirerListener
{
    protected $missionId = 'inquirer';

    /**
     * Handle the event.
     * ماموریت پرسشگر فعال: کاربری که بیشترین امتیاز برای پرسش خود در یک بازه زمانی مشخص (مثلاً ماهانه یا هفتگی) کسب کرده است.
     *
     * @param  CommunityActivityEvent  $event
     * @return void
     */
    public function handle(CommunityActivityEvent $event)
    {
        if ($event->type !== 'question') {
            return;
        }

        $monthStart = Carbon::now()->startOfMonth();
        
        // تعداد سوالات کاربر در این ماه
        $questionsCount = $event->user->questions()
            ->where('created_at', '>=', $monthStart)
            ->count();

        sync_mission_progress_for_user($event->user->id, $this->missionId, (int) $questionsCount);
    }
}
