<?php

namespace App\Listeners\Mission\Purchases;

use App\Events\Mission\PurchaseEvent;
use Carbon\Carbon;

class FastPurchaseListener
{
    protected $missionId = 'fast-purchase';

    /**
     * Handle the event.
     * ماموریت خریدار سریع: کاربری که در 24 ساعت اول پس از انتشار دوره، آن را خریداری کرده است.
     *
     * @param  PurchaseEvent  $event
     * @return void
     */
    public function handle(PurchaseEvent $event)
    {
        if (!$event->course) {
            return;
        }

        $coursePublishedAt = $event->course->created_at;
        $purchaseTime = now();
        
        // بررسی اینکه آیا خرید در 24 ساعت اول پس از انتشار بوده است
        if ($purchaseTime->diffInHours($coursePublishedAt) <= 24) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
