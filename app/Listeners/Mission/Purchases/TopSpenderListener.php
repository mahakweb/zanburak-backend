<?php

namespace App\Listeners\Mission\Purchases;

use App\Events\Mission\PurchaseEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TopSpenderListener
{
    protected $missionId = 'top-spender';

    /**
     * Handle the event.
     * ماموریت خریدار برتر: کاربری که بیشترین تعداد خرید دوره آموزشی در یک هفته را داشته است.
     *
     * @param  PurchaseEvent  $event
     * @return void
     */
    public function handle(PurchaseEvent $event)
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        
        // تعداد خریدهای کاربر در این هفته
        $userPurchasesThisWeek = $event->user->courses()
            ->wherePivot('created_at', '>=', $weekStart)
            ->wherePivot('created_at', '<=', $weekEnd)
            ->count();
        
        // بیشترین تعداد خرید در این هفته توسط هر کاربر
        $maxPurchasesThisWeek = DB::table('course_user')
            ->where('created_at', '>=', $weekStart)
            ->where('created_at', '<=', $weekEnd)
            ->select(DB::raw('user_id, COUNT(*) as count'))
            ->groupBy('user_id')
            ->orderBy('count', 'desc')
            ->value('count') ?? 0;
        
        // اگر کاربر بیشترین خرید را داشته باشد
        if ($userPurchasesThisWeek > 0 && $userPurchasesThisWeek >= $maxPurchasesThisWeek) {
            upgrade_mission_for_user($event->user->id, $this->missionId, $userPurchasesThisWeek, 1);
        }
    }
}
