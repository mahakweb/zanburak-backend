<?php

namespace App\Listeners\Mission\Purchases;

use App\Events\Mission\PurchaseEvent;

class LoyalBuyerListener
{
    protected $missionId = 'loyal-buyer';

    /**
     * Handle the event.
     * ماموریت خریدار متعهد: کاربری که حداقل 5 دوره آموزشی را خریداری کرده است. (5 سطح دارد)
     *
     * @param  PurchaseEvent  $event
     * @return void
     */
    public function handle(PurchaseEvent $event)
    {
        $userCourseCount = $event->user->courses()->count();
        upgrade_mission_for_user($event->user->id, $this->missionId, $userCourseCount, 1);
    }
}
