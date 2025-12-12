<?php

namespace App\Listeners\Mission\Purchases;

use App\Events\Mission\PurchaseEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DiscountPurchaseListener
{
    protected $missionId = 'discount-purchase';

    /**
     * Handle the event.
     * ماموریت خریدار اقتصادی: کاربری که بیشترین تعداد دوره‌ها را در یک بازه تخفیف ویژه خریداری کرده است.
     *
     * @param  PurchaseEvent  $event
     * @return void
     */
    public function handle(PurchaseEvent $event)
    {
        if (!$event->payment) {
            return;
        }

        // بررسی اینکه آیا این خرید با تخفیف بوده است
        $hasDiscount = $event->payment->discount_code || 
                      $event->payment->items()->whereNotNull('discount_code')->exists();

        if ($hasDiscount) {
            // تعداد خریدهای با تخفیف در بازه زمانی فعلی (مثلاً این ماه)
            $monthStart = Carbon::now()->startOfMonth();
            $discountPurchasesCount = $event->user->payments()
                ->where('created_at', '>=', $monthStart)
                ->where(function($query) {
                    $query->whereNotNull('discount_code')
                          ->orWhereHas('items', function($q) {
                              $q->whereNotNull('discount_code');
                          });
                })
                ->count();

            upgrade_mission_for_user($event->user->id, $this->missionId, $discountPurchasesCount, 1);
        }
    }
}
