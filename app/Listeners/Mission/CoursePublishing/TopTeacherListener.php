<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;
use App\Events\Mission\PurchaseEvent;
use App\Models\User;

class TopTeacherListener
{
    protected $missionId = 'top-teacher';

    /**
     * Handle the event.
     * ماموریت مدرس محبوب: مدرسی که دوره‌ای با حداقل 100 خرید را در هفته اول انتشار دوره داشته باشد. (سطح دارد)
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent|PurchaseEvent $event)
    {
        if ($event instanceof PurchaseEvent) {
            $course = $event->course;
            $teacher = $course?->teacher;
            if (!$teacher && $course?->teacher_id) {
                $teacher = User::find($course->teacher_id);
            }
        } else {
            $course = $event->course;
            $teacher = $event->user;
        }

        if (!$course || !$teacher || !$course->created_at) {
            return;
        }

        $publishedAt = $course->created_at;
        $firstWeekEnd = $publishedAt->copy()->addWeek();

        $purchasesInFirstWeek = $course->users()
            ->wherePivot('created_at', '>=', $publishedAt)
            ->wherePivot('created_at', '<=', $firstWeekEnd)
            ->count();

        if ($purchasesInFirstWeek < 100) {
            return;
        }

        $popularCoursesCount = $teacher->addCourse()
            ->where('publish', true)
            ->get()
            ->filter(function ($c) {
                if (!$c->created_at) {
                    return false;
                }
                $firstWeekEnd = $c->created_at->copy()->addWeek();

                return $c->users()
                    ->wherePivot('created_at', '>=', $c->created_at)
                    ->wherePivot('created_at', '<=', $firstWeekEnd)
                    ->count() >= 100;
            })
            ->count();

        sync_mission_progress_for_user($teacher->id, $this->missionId, (int) $popularCoursesCount);

        if ($popularCoursesCount >= 5) {
            sync_mission_progress_for_user($teacher->id, 'top-seller-teacher', 1);
        }
    }
}
