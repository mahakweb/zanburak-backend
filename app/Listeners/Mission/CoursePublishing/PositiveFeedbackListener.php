<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;
use App\Events\Score\Rating\RatingSubmitted;
use App\Models\Course;
use App\Models\User;

class PositiveFeedbackListener
{
    protected $missionId = 'positive-feedback';

    /**
     * Handle the event.
     * ماموریت بازخورد مثبت: مدرسی که برای یک دوره حداقل 1000 بازخورد مثبت و امتیاز بالا را از کاربران دریافت کرده است.
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent|RatingSubmitted $event)
    {
        [$course, $teacher] = $this->courseAndTeacher($event);
        if (!$course || !$teacher) {
            return;
        }

        $likesCount = $course->likes()->where('type', 'like')->count();
        $avgRating = $course->ratings()->avg('rating') ?? 0;

        if ($likesCount >= 1000 || $avgRating >= 4.5) {
            upgrade_mission_for_user($teacher->id, $this->missionId);
        }
    }

    protected function courseAndTeacher(CoursePublishingEvent|RatingSubmitted $event): array
    {
        if ($event instanceof RatingSubmitted) {
            $course = $event->rating->rateable;
            if (!$course instanceof Course) {
                return [null, null];
            }
            $teacher = $course->teacher ?: ($course->teacher_id ? User::find($course->teacher_id) : null);

            return [$course, $teacher];
        }

        return [$event->course, $event->user];
    }
}
