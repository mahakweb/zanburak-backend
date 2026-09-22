<?php

namespace App\Listeners\Mission\CoursePublishing;

use App\Events\Mission\CoursePublishingEvent;
use App\Events\Mission\PurchaseEvent;
use App\Events\Score\Rating\RatingSubmitted;
use App\Models\Course;
use App\Models\User;
use App\Support\SqlDialect;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PublishingExtrasListener
{
    public function handle(CoursePublishingEvent|PurchaseEvent|RatingSubmitted $event): void
    {
        if ($event instanceof CoursePublishingEvent) {
            $this->onPublish($event->user);
            return;
        }

        if ($event instanceof PurchaseEvent) {
            $teacher = $this->teacherFromCourse($event->course);
            if ($teacher) {
                $this->syncPopularCourses($teacher);
            }
            return;
        }

        $rateable = $event->rating->rateable ?? null;
        if (!$rateable instanceof Course) {
            return;
        }

        $teacher = $rateable->teacher ?: ($rateable->teacher_id ? User::find($rateable->teacher_id) : null);
        if (!$teacher) {
            return;
        }

        $this->syncTopMaster($teacher, $rateable);
    }

    protected function onPublish(User $teacher): void
    {
        $monthStart = Carbon::now()->startOfMonth();

        $published = $teacher->addCourse()
            ->where('publish', true)
            ->where('created_at', '>=', $monthStart)
            ->count();

        $maxPublished = (int) DB::table('courses')
            ->where('publish', true)
            ->where('created_at', '>=', $monthStart)
            ->selectRaw('teacher_id, COUNT(*) as total')
            ->groupBy('teacher_id')
            ->get()
            ->max('total');

        if ($published > 0 && $published >= $maxPublished) {
            upgrade_mission_for_user($teacher->id, 'productive-teacher');
        }

        $seconds = $this->publishedSeconds($teacher->id, $monthStart);
        $maxSeconds = (int) DB::table('episodes')
            ->join('sections', 'sections.id', '=', 'episodes.section_id')
            ->join('courses', 'courses.id', '=', 'sections.course_id')
            ->where('courses.publish', true)
            ->where('courses.created_at', '>=', $monthStart)
            ->selectRaw('courses.teacher_id, SUM('.SqlDialect::castInt('episodes.total_time').') as seconds')
            ->groupBy('courses.teacher_id')
            ->get()
            ->max('seconds');

        if ($seconds > 0 && $seconds >= $maxSeconds) {
            upgrade_mission_for_user($teacher->id, 'active-teacher');
        }

        $months = DB::table('courses')
            ->where('teacher_id', $teacher->id)
            ->where('publish', true)
            ->selectRaw(SqlDialect::yearMonth('created_at').' as ym')
            ->groupBy('ym')
            ->get()
            ->count();

        $sales = DB::table('course_user')
            ->whereIn('course_id', function ($query) use ($teacher) {
                $query->select('id')->from('courses')->where('teacher_id', $teacher->id);
            })
            ->count();

        if ($months >= 3 && $sales >= 1) {
            upgrade_mission_for_user($teacher->id, 'consistent-publisher');
        }
    }

    protected function syncPopularCourses(User $teacher): void
    {
        $popular = Course::query()
            ->where('teacher_id', $teacher->id)
            ->where('publish', true)
            ->has('users', '>=', 10)
            ->count();

        sync_mission_progress_for_user($teacher->id, 'popular-courses', (int) $popular);
    }

    protected function syncTopMaster(User $teacher, Course $course): void
    {
        $ratingsCount = $course->ratings()->count();
        $average = (float) ($course->ratings()->avg('rating') ?? 0);

        if ($ratingsCount < 3 || $average < 4.5) {
            return;
        }

        upgrade_mission_for_user($teacher->id, 'top-master');
    }

    protected function teacherFromCourse($course): ?User
    {
        if (!$course) {
            return null;
        }

        return $course->teacher ?: ($course->teacher_id ? User::find($course->teacher_id) : null);
    }

    protected function publishedSeconds(int $teacherId, $since): int
    {
        return (int) DB::table('episodes')
            ->join('sections', 'sections.id', '=', 'episodes.section_id')
            ->join('courses', 'courses.id', '=', 'sections.course_id')
            ->where('courses.teacher_id', $teacherId)
            ->where('courses.publish', true)
            ->where('courses.created_at', '>=', $since)
            ->sum(DB::raw(SqlDialect::castInt('episodes.total_time')));
    }
}
