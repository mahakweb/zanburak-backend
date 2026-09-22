<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;

class TopResponderListener
{
    protected $missionId = 'top-responder';

    /**
     * Handle the event.
     * ماموریت پاسخ‌دهنده حرفه‌ای: کاربری که بیشترین تعداد پاسخ مفید (تایید شده توسط دیگر کاربران) به کامنت‌های دیگران داده است.
     * مجموع پاسخ هایی که حداقل 50 نظر مثبت گرفته باشند.
     *
     * @param  CommunityActivityEvent  $event
     * @return void
     */
    public function handle(CommunityActivityEvent $event)
    {
        if ($event->type !== 'answer') {
            return;
        }

        $helpfulAnswersCount = $event->user->answers()
            ->whereHas('likes', function ($query) {
                $query->where('type', 'like');
            }, '>=', 50)
            ->count();

        sync_mission_progress_for_user($event->user->id, $this->missionId, (int) $helpfulAnswersCount);
    }
}
