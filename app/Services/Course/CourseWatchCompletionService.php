<?php

namespace App\Services\Course;

use App\Events\Course\CourseCompleted;
use App\Events\Mission\CourseCompletionEvent;
use App\Models\Course;
use App\Models\User;

class CourseWatchCompletionService
{
    /**
     * Persisted completion: course_user.completed_at is set.
     */
    public function hasStoredCompletion(User $user, Course $course): bool
    {
        return $user->courses()
            ->where('course_id', $course->id)
            ->whereNotNull('completed_at')
            ->exists();
    }

    /**
     * Live check: all published episodes fully watched (from video_views).
     */
    public function hasWatchedAllVideos(User $user, Course $course): bool
    {
        return $course->isCompletedByUser($user->id);
    }

    /**
     * Record completion when the user finishes every episode.
     * Returns true if this call newly marked the course complete.
     */
    public function markIfFullyWatched(User $user, Course $course): bool
    {
        if (! $this->hasWatchedAllVideos($user, $course)) {
            return false;
        }

        if ($this->hasStoredCompletion($user, $course)) {
            return false;
        }

        $user->courses()->syncWithoutDetaching([
            $course->id => ['completed_at' => now()],
        ]);

        event(new CourseCompleted($user, $course));
        event(new CourseCompletionEvent($user, $course, now()));

        return true;
    }
}
