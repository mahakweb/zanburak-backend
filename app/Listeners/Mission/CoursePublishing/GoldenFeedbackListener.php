<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;
use App\Events\Score\Rating\RatingSubmitted;
use App\Models\Course;
use App\Models\User;

class GoldenFeedbackListener
{
    protected $missionId = 'golden-feedback';

    /**
     * Handle the event.
     * ماموریت بازخورد طلایی: مدرسی که برای 5 دوره، ماموریت بازخورد مثبت دریافت کرده است.
     *
     * @param  CoursePublishingEvent  $event
     * @return void
     */
    public function handle(CoursePublishingEvent|RatingSubmitted $event)
    {
        $teacher = $event instanceof RatingSubmitted
            ? $this->teacherFromRating($event)
            : $event->user;

        if (!$teacher) {
            return;
        }

        // تعداد دوره‌هایی که بازخورد مثبت دارند (بیش از 1000 لایک یا ریتینگ بالا)
        $coursesWithPositiveFeedback = $teacher->addCourse()
            ->where('publish', true)
            ->get()
            ->filter(function($course) {
                $likesCount = $course->likes()->where('type', 'like')->count();
                $avgRating = $course->ratings()->avg('rating') ?? 0;
                return $likesCount >= 1000 || $avgRating >= 4.5;
            })
            ->count();

        if ($coursesWithPositiveFeedback >= 5) {
            upgrade_mission_for_user($teacher->id, $this->missionId);
        }
    }

    protected function teacherFromRating(RatingSubmitted $event): ?User
    {
        $course = $event->rating->rateable;
        if (!$course instanceof Course) {
            return null;
        }

        return $course->teacher ?: ($course->teacher_id ? User::find($course->teacher_id) : null);
    }
}
