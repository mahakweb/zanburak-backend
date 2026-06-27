<?php

namespace App\Services\Course;

use App\Models\Course;
use App\Models\Episode;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CourseAvailabilityService
{
    public const REASON_COURSE_PRESALE = 'course_presale';

    public const REASON_COURSE_UPCOMING = 'course_upcoming';

    public const REASON_COURSE_NOT_STARTED = 'course_not_started';

    public const REASON_SECTION_NOT_STARTED = 'section_not_started';

    public const REASON_EPISODE_NOT_PUBLISHED = 'episode_not_published';

    public const REASON_COURSE_ARCHIVED = 'course_archived';

    /** Whether the course is in pre-sale (reads status record via status_id, not a separate column). */
    public function isPresale(Course $course): bool
    {
        $course->loadMissing('status');

        return $course->status?->english_title === 'presale';
    }

    public function isUpcoming(Course $course): bool
    {
        $course->loadMissing('status');

        return $course->status?->english_title === 'upcoming';
    }

    public function isArchive(Course $course): bool
    {
        $course->loadMissing('status');

        return $course->status?->english_title === 'archive';
    }

    public function isPurchasable(Course $course): bool
    {
        return ! $this->isArchive($course);
    }

    /** Archive courses: only users with an existing enrollment/purchase record may access content. */
    public function userHasCourseAccess(?\App\Models\User $user, Course $course): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isArchive($course)) {
            return $user->courses()->where('courses.id', $course->id)->exists();
        }

        return $user->hasCourse($course);
    }

    public function isCourseStarted(Course $course): bool
    {
        if (! $course->start_date) {
            return true;
        }

        return Carbon::parse($course->start_date)->lte(now());
    }

    /** Whether any course video content may be watched (presale / upcoming / start_date gate). end_date does not block. */
    public function canWatchVideos(Course $course): bool
    {
        if ($this->isPresale($course) || $this->isUpcoming($course)) {
            return false;
        }

        return $this->isCourseStarted($course);
    }

    public function courseAvailabilityMeta(Course $course): array
    {
        $canWatch = $this->canWatchVideos($course);
        $reason = null;
        $availableAt = null;
        $message = null;

        if ($this->isPresale($course)) {
            $reason = self::REASON_COURSE_PRESALE;
            $availableAt = $course->start_date ? Carbon::parse($course->start_date)->toIso8601String() : null;
            $message = $availableAt
                ? 'این دوره در مرحله پیش‌فروش است. محتوا از '.jdate($course->start_date)->format('Y/m/d H:i').' در دسترس خواهد بود.'
                : 'این دوره در مرحله پیش‌فروش است و هنوز محتوای ویدیویی باز نشده است.';
        } elseif ($this->isUpcoming($course)) {
            $reason = self::REASON_COURSE_UPCOMING;
            $availableAt = $course->start_date ? Carbon::parse($course->start_date)->toIso8601String() : null;
            $message = $availableAt
                ? 'این دوره به‌زودی منتشر می‌شود. تاریخ شروع: '.jdate($course->start_date)->format('Y/m/d H:i')
                : 'این دوره به‌زودی منتشر می‌شود و هنوز محتوای ویدیویی باز نشده است.';
        } elseif (! $this->isCourseStarted($course)) {
            $reason = self::REASON_COURSE_NOT_STARTED;
            $availableAt = Carbon::parse($course->start_date)->toIso8601String();
            $message = 'این دوره هنوز شروع نشده است. تاریخ شروع: '.jdate($course->start_date)->format('Y/m/d H:i');
        }

        $isArchive = $this->isArchive($course);
        if ($isArchive) {
            $message = 'این دوره آرشیو شده است. امکان خرید یا ثبت‌نام جدید وجود ندارد.';
        }

        return [
            'is_presale' => $this->isPresale($course),
            'is_archive' => $isArchive,
            'is_purchasable' => $this->isPurchasable($course),
            'can_watch_videos' => $canWatch,
            'course_started' => $this->isCourseStarted($course),
            'start_date' => $course->start_date,
            'end_date' => $course->end_date,
            'lock_reason' => $reason,
            'available_at' => $availableAt,
            'message' => $message,
            'last_content_update' => $this->getCourseLastUpdate($course)?->toIso8601String(),
        ];
    }

    public function sectionAvailability(Section $section, ?Course $course = null): array
    {
        $course = $course ?? $section->course ?? Course::find($section->course_id);
        $courseCheck = $this->courseAvailabilityMeta($course);

        if (! $courseCheck['can_watch_videos']) {
            return [
                'is_available' => false,
                'is_playable' => false,
                'lock_reason' => $courseCheck['lock_reason'],
                'available_at' => $courseCheck['available_at'],
                'message' => $courseCheck['message'],
            ];
        }

        if ($section->start_date && Carbon::parse($section->start_date)->isFuture()) {
            $at = Carbon::parse($section->start_date);

            return [
                'is_available' => false,
                'is_playable' => false,
                'lock_reason' => self::REASON_SECTION_NOT_STARTED,
                'available_at' => $at->toIso8601String(),
                'message' => 'این فصل از '.jdate($at)->format('Y/m/d H:i').' باز می‌شود.',
            ];
        }

        return [
            'is_available' => true,
            'is_playable' => true,
            'lock_reason' => null,
            'available_at' => null,
            'message' => null,
        ];
    }

    public function episodeAvailability(Episode $episode, ?Section $section = null, ?Course $course = null): array
    {
        $section = $section ?? $episode->section ?? Section::find($episode->section_id);
        $course = $course ?? $section?->course ?? Course::find($section?->course_id);

        $sectionCheck = $this->sectionAvailability($section, $course);
        if (! $sectionCheck['is_available']) {
            return [
                'is_available' => false,
                'is_playable' => false,
                'lock_reason' => $sectionCheck['lock_reason'],
                'available_at' => $sectionCheck['available_at'],
                'message' => $sectionCheck['message'],
            ];
        }

        if ($episode->publish_date && Carbon::parse($episode->publish_date)->isFuture()) {
            $at = Carbon::parse($episode->publish_date);

            return [
                'is_available' => false,
                'is_playable' => false,
                'lock_reason' => self::REASON_EPISODE_NOT_PUBLISHED,
                'available_at' => $at->toIso8601String(),
                'message' => 'این جلسه از '.jdate($at)->format('Y/m/d H:i').' منتشر می‌شود.',
            ];
        }

        return [
            'is_available' => true,
            'is_playable' => true,
            'lock_reason' => null,
            'available_at' => null,
            'message' => null,
        ];
    }

    public function assertEpisodePlayable(Episode $episode): ?array
    {
        $check = $this->episodeAvailability($episode);
        if ($check['is_playable']) {
            return null;
        }

        return [
            'error' => 'content_not_available',
            'code' => $check['lock_reason'],
            'message' => $check['message'],
            'available_at' => $check['available_at'],
        ];
    }

    public function assertEpisodeViewable(Episode $episode, ?\App\Models\User $user): ?array
    {
        $playBlock = $this->assertEpisodePlayable($episode);
        if ($playBlock) {
            return $playBlock;
        }

        $episode->loadMissing('section.course.status');
        $course = $episode->section?->course;

        if ($course && $this->isArchive($course) && ! $this->userHasCourseAccess($user, $course)) {
            return [
                'error' => 'content_not_available',
                'code' => self::REASON_COURSE_ARCHIVED,
                'message' => 'این دوره آرشیو شده است. امکان دسترسی به محتوا وجود ندارد.',
                'available_at' => null,
            ];
        }

        return null;
    }

    public function enrichCourseTree(Course $course): void
    {
        $meta = $this->courseAvailabilityMeta($course);
        $course->setAttribute('availability', $meta);
        $course->setAttribute('is_presale', $meta['is_presale']);
        $course->setAttribute('can_watch_videos', $meta['can_watch_videos']);
        $course->setAttribute('last_content_update', $meta['last_content_update']);

        foreach ($course->section as $section) {
            $sectionMeta = $this->sectionAvailability($section, $course);
            $section->setAttribute('availability', $sectionMeta);
            $section->setAttribute('is_available', $sectionMeta['is_available']);
            $section->setAttribute('is_playable', $sectionMeta['is_playable']);

            foreach ($section->episode as $episodeItem) {
                $epMeta = $this->episodeAvailability($episodeItem, $section, $course);
                $episodeItem->setAttribute('availability', $epMeta);
                $episodeItem->setAttribute('is_playable', $epMeta['is_playable']);
                $episodeItem->setAttribute('is_available', $epMeta['is_available']);
            }
        }
    }

    public function getCourseLastUpdate(Course $course): Carbon
    {
        if (! $course->relationLoaded('section')) {
            $course->load(['section.episode' => fn ($q) => $q->where('publish', 1)]);
        }

        $latest = null;

        foreach ($course->section as $section) {
            foreach ($section->episode as $episode) {
                if ((int) $episode->publish !== 1) {
                    continue;
                }

                $candidate = $episode->publish_date
                    ? Carbon::parse($episode->publish_date)
                    : Carbon::parse($episode->created_at);

                if (! $latest || $candidate->gt($latest)) {
                    $latest = $candidate;
                }
            }
        }

        return $latest ?? Carbon::parse($course->updated_at);
    }

    public function listItemMeta(Course $course): array
    {
        $meta = $this->courseAvailabilityMeta($course);

        return [
            'is_presale' => $meta['is_presale'],
            'is_archive' => $meta['is_archive'],
            'is_purchasable' => $meta['is_purchasable'],
            'can_watch_videos' => $meta['can_watch_videos'],
            'start_date' => $course->start_date,
            'status' => $course->relationLoaded('status') ? $course->status : $course->status()->first(['id', 'title', 'english_title', 'slug']),
            'last_content_update' => $meta['last_content_update'],
        ];
    }
}
