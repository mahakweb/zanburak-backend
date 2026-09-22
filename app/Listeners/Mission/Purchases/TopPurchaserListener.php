<?php

namespace App\Listeners\Mission\Purchases;

use App\Events\Mission\PurchaseEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TopPurchaserListener
{
    protected $missionId = 'top-purchaser';

    /**
     * Handle the event.
     * ماموریت حامی سایت: کاربری که بیشترین هزینه را برای خرید دوره‌ها در یکماه انجام داده است.
     *
     * @param  PurchaseEvent  $event
     * @return void
     */
    public function handle(PurchaseEvent $event)
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        // مجموع هزینه‌های کاربر در این ماه
        $userTotalSpent = $event->user->payments()
            ->where('status', true)
            ->whereNotNull('paid_at')
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<=', $monthEnd)
            ->sum('amount');

        // بیشترین هزینه در این ماه توسط هر کاربر
        $maxSpentThisMonth = DB::table('payments')
            ->where('status', true)
            ->whereNotNull('paid_at')
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<=', $monthEnd)
            ->select(DB::raw('user_id, SUM(amount) as total'))
            ->groupBy('user_id')
            ->orderBy('total', 'desc')
            ->value('total') ?? 0;

        // اگر کاربر بیشترین هزینه را داشته باشد
        if ($userTotalSpent > 0 && $userTotalSpent >= $maxSpentThisMonth) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
