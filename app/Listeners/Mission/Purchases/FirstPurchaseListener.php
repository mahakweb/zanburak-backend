<?php

namespace App\Listeners\Mission\Purchases;

class FirstPurchaseListener
{
    protected $missionId = 'first-purchase';

    public function handle($event)
    {
        $userCourseCount = $event->user->courses()->count();
        if ($userCourseCount == 1) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
