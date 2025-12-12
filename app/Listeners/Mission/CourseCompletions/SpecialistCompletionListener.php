<?php

namespace App\Listeners\Mission\CourseCompletions;

use App\Events\Mission\CourseCompletionEvent;
use Illuminate\Support\Facades\DB;

class SpecialistCompletionListener
{
    protected $missionId = 'specialist-completion';

    /**
     * Handle the event.
     * ماموریت تخصص‌یافته: کاربری که حداقل 10 دوره از یک دسته‌بندی خاص را به پایان رسانده است.
     *
     * @param  CourseCompletionEvent  $event
     * @return void
     */
    public function handle(CourseCompletionEvent $event)
    {
        $courseCategories = $event->course->category;

        foreach ($courseCategories as $category) {
            // تعداد دوره‌های تکمیل شده توسط کاربر در این دسته‌بندی
            $completedInCategory = $event->user->courses()
                ->whereHas('category', function($query) use ($category) {
                    $query->where('categories.id', $category->id);
                })
                ->wherePivotNotNull('completed_at')
                ->count();

            upgrade_mission_for_user($event->user->id, $this->missionId, $completedInCategory, 1);
        }
    }
}
