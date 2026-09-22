<?php

namespace App\Listeners\Score\Payment;

use App\Events\Payment\CoursePurchased;

class FirstPurchaseListener
{
    public function handle(CoursePurchased $event)
    {
        // اولین خرید توسط Mission\Purchases\FirstPurchaseListener روی PurchaseEvent انجام می‌شود.
        // اینجا فقط امتیاز هر خرید از ماموریت course-purchase خوانده می‌شود.
        award_mission_exp(
            $event->user,
            'course-purchase',
            "خرید دوره: {$event->course->title}"
        );
        upgrade_mission_for_user($event->user->id, 'course-purchase', 1, 1, false);
    }
}
