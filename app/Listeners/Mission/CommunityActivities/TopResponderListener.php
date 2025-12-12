<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        // تعداد پاسخ‌هایی که حداقل 50 لایک دارند
        $helpfulAnswersCount = $event->user->answers()
            ->whereHas('likes', function($query) {
                $query->where('type', 'like')
                      ->selectRaw('COUNT(*) as likes_count')
                      ->havingRaw('likes_count >= 50');
            })
            ->count();

        upgrade_mission_for_user($event->user->id, $this->missionId, $helpfulAnswersCount, 1);
    }
}
