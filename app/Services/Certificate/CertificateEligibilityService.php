<?php

namespace App\Services\Certificate;

use App\Models\Course;
use App\Models\User;

class CertificateEligibilityService
{
    /**
     * Whether a student may receive an auto-issued certificate for this course.
     */
    public function canAutoIssue(User $user, Course $course): bool
    {
        if (! $course->certificate_enabled) {
            return false;
        }

        if (! $user->hasCourse($course)) {
            return false;
        }

        if (! $course->isRecordingComplete()) {
            return false;
        }

        if (! $course->isCompletedByUser($user->id)) {
            return false;
        }

        return true;
    }

    /**
     * Record course completion on the pivot (works for free courses too).
     */
    public function markCourseCompleted(User $user, Course $course): void
    {
        $user->courses()->syncWithoutDetaching([
            $course->id => ['completed_at' => now()],
        ]);
    }
}
