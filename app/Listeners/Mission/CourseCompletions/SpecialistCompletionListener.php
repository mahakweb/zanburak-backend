<?php

namespace App\Listeners\Mission\CourseCompletions;

use App\Events\Mission\CourseCompletionEvent;

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
        $completedInCategory = 0;
        foreach ($event->course->category as $category) {
            $completedInCategory = max($completedInCategory, $event->user->courses()
                ->whereHas('category', function ($query) use ($category) {
                    $query->where('categories.id', $category->id);
                })
                ->wherePivotNotNull('completed_at')
                ->count());
        }

        sync_mission_progress_for_user($event->user->id, $this->missionId, (int) $completedInCategory);
    }
}
