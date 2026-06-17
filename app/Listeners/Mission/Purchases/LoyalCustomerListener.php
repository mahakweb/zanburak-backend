<?php

namespace App\Listeners\Mission\Purchases;

use App\Support\SqlDialect;
use App\Events\Mission\PurchaseEvent;
use Carbon\Carbon;

class LoyalCustomerListener
{
    protected $missionId = 'loyal-customer';

    /**
     * Handle the event.
     * ماموریت خریدار وفادار: کاربری که به صورت مداوم در طی یک سال چندین دوره آموزشی خریداری کرده است.
     *
     * @param  PurchaseEvent  $event
     * @return void
     */
    public function handle(PurchaseEvent $event)
    {
        $oneYearAgo = Carbon::now()->subYear();
        
        // بررسی اینکه آیا کاربر در 12 ماه گذشته خرید داشته است
        $purchasesLastYear = $event->user->courses()
            ->wherePivot('created_at', '>=', $oneYearAgo)
            ->count();

        // بررسی اینکه آیا خریدها در ماه‌های مختلف توزیع شده‌اند (حداقل 3 ماه)
        $monthsWithPurchases = $event->user->courses()
            ->wherePivot('created_at', '>=', $oneYearAgo)
            ->selectRaw(SqlDialect::year('created_at').' as year, '.SqlDialect::month('created_at').' as month')
            ->groupBy('year', 'month')
            ->get()
            ->count();

        if ($purchasesLastYear >= 3 && $monthsWithPurchases >= 3) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
