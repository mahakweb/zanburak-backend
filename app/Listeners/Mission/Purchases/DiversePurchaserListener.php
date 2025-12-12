<?php

namespace App\Listeners\Mission\Purchases;

use App\Events\Mission\PurchaseEvent;
use Illuminate\Support\Facades\DB;

class DiversePurchaserListener
{
    protected $missionId = 'diverse-purchaser';

    /**
     * Handle the event.
     * ماموریت خریدار متنوع: کاربری که حداقل 2 دوره‌های آموزشی از 5 حوزه مختلف خریداری کرده است.
     *
     * @param  PurchaseEvent  $event
     * @return void
     */
    public function handle(PurchaseEvent $event)
    {
        // تعداد دسته‌بندی‌های مختلف که کاربر از هر کدام حداقل 2 دوره خریداری کرده
        $diverseCategoriesCount = DB::table('course_user')
            ->join('category_course', 'course_user.course_id', '=', 'category_course.course_id')
            ->where('course_user.user_id', $event->user->id)
            ->select('category_course.category_id', DB::raw('COUNT(DISTINCT course_user.course_id) as course_count'))
            ->groupBy('category_course.category_id')
            ->having('course_count', '>=', 2)
            ->count();

        upgrade_mission_for_user($event->user->id, $this->missionId, $diverseCategoriesCount, 1);
    }
}
