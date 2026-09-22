<?php

namespace App\Listeners\Mission\Purchases;

use App\Events\Mission\PurchaseEvent;

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
            $discountPurchasesCount = $event->user->payments()
                ->where('status', true)
                ->whereNotNull('paid_at')
                ->where(function ($query) {
                    $query->whereNotNull('discount_code')
                          ->orWhereHas('items', function ($q) {
                              $q->whereNotNull('discount_code');
                          });
                })
                ->count();

            sync_mission_progress_for_user($event->user->id, $this->missionId, (int) $discountPurchasesCount);
        }
    }
}
