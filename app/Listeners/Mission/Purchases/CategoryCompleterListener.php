<?php

namespace App\Listeners\Mission\Purchases;

use App\Events\Mission\PurchaseEvent;
use App\Models\Category;

class CategoryCompleterListener
{
    protected $missionId = 'category-completer';

    /**
     * Handle the event.
     * ماموریت تکمیل کننده: کاربری که تمامی دوره‌های موجود در یک دسته‌بندی خاص را خریداری کرده است.
     *
     * @param  PurchaseEvent  $event
     * @return void
     */
    public function handle(PurchaseEvent $event)
    {
        if (!$event->course) {
            return;
        }

        // دریافت دسته‌بندی‌های دوره خریداری شده
        $courseCategories = $event->course->category;

        foreach ($courseCategories as $category) {
            // تعداد کل دوره‌های منتشر شده در این دسته‌بندی
            $totalCoursesInCategory = $category->course()
                ->where('publish', true)
                ->count();

            // تعداد دوره‌های خریداری شده توسط کاربر در این دسته‌بندی
            $userCoursesInCategory = $event->user->courses()
                ->whereHas('category', function($query) use ($category) {
                    $query->where('categories.id', $category->id);
                })
                ->count();

            // اگر کاربر تمام دوره‌های این دسته‌بندی را خریداری کرده باشد
            if ($totalCoursesInCategory > 0 && $userCoursesInCategory >= $totalCoursesInCategory) {
                upgrade_mission_for_user($event->user->id, $this->missionId);
            }
        }
    }
}
