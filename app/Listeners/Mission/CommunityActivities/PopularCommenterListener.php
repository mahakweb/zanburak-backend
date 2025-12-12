<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;

class PopularCommenterListener
{
    protected $missionId = 'popular-commenter';

    /**
     * Handle the event.
     * ماموریت کامنت‌های پرطرفدار: کاربری که کامنت‌هایش بیشترین تعداد لایک یا رای مثبت را از سایر کاربران دریافت کرده است.
     *
     * @param  CommunityActivityEvent  $event
     * @return void
     */
    public function handle(CommunityActivityEvent $event)
    {
        if ($event->type !== 'comment') {
            return;
        }

        // تعداد کل لایک‌های کامنت‌های کاربر
        $totalLikes = $event->user->comments()
            ->withCount(['likes' => function($query) {
                $query->where('type', 'like');
            }])
            ->get()
            ->sum('likes_count');

        upgrade_mission_for_user($event->user->id, $this->missionId, $totalLikes, 1);
    }
}
