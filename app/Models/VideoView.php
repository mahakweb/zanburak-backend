<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class VideoView extends Model
{
    use HasFactory;

    /** Inactivity gap — after this, the next progress write opens a new session row. */
    public const SESSION_GAP_HOURS = 1;

    public const TIME_SESSION_START = 'session';

    public const TIME_ACTIVITY = 'activity';

    protected $fillable = ['user_id', 'video_id', 'watched_times', 'last_position', 'watched'];

    protected $casts = [
        'watched_times' => 'array',
        'watched' => 'boolean',
        'last_position' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public static function normalizeWatchedTimes(mixed $value): array
    {
        if (is_array($value)) {
            return self::uniqueSecondIndices($value);
        }
        if (empty($value)) {
            return [];
        }

        return self::uniqueSecondIndices(json_decode($value, true) ?? []);
    }

    /** Remove duplicate second indices; keep non-negative integers only. */
    public static function uniqueSecondIndices(array $times): array
    {
        $ints = array_map('intval', $times);

        return array_values(array_unique(array_filter($ints, fn (int $s) => $s >= 0)));
    }

    /** Required unique second indices for completion (matches VideoPlayer: floor(duration)). */
    public static function requiredSecondCount(Video $video): int
    {
        return max(0, (int) floor((float) $video->duration));
    }

    /** All second indices 0 … required−1 that the user has not yet watched. */
    public static function getMissingSecondIndices(int $userId, int $videoId, ?Video $video = null): array
    {
        $video = $video ?? Video::find($videoId);
        if (! $video?->duration) {
            return [];
        }

        $required = self::requiredSecondCount($video);
        if ($required === 0) {
            return [];
        }

        $watched = self::getAllWatchedTimes($userId, $videoId);
        $watchedLookup = array_flip($watched);
        $missing = [];

        for ($i = 0; $i < $required; $i++) {
            if (! isset($watchedLookup[$i])) {
                $missing[] = $i;
            }
        }

        return $missing;
    }

    public static function getWatchCoveragePercent(int $userId, int $videoId, ?Video $video = null): float
    {
        $video = $video ?? Video::find($videoId);
        if (! $video?->duration) {
            return 0.0;
        }

        $required = self::requiredSecondCount($video);
        if ($required === 0) {
            return 100.0;
        }

        $watched = self::getMergedWatchedSeconds($userId, $videoId);

        return min(100.0, round(($watched / $required) * 100, 2));
    }

    /**
     * Whether the latest row is still the same viewing session.
     * A new session starts after inactivity OR on a new calendar day.
     */
    public static function isSessionStillActive(self $row): bool
    {
        $now = now();
        $lastActivity = Carbon::parse($row->updated_at);

        if ($lastActivity->lt($now->copy()->subHours(self::SESSION_GAP_HOURS))) {
            return false;
        }

        if (! $lastActivity->isSameDay($now)) {
            return false;
        }

        return true;
    }

    /**
     * Latest row for this user+video if still the same session; otherwise null → create a new row.
     */
    public static function findActiveSession(int $userId, int $videoId): ?self
    {
        $latest = self::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->orderByDesc('id')
            ->first();

        if (! $latest || ! self::isSessionStillActive($latest)) {
            return null;
        }

        return $latest;
    }

    public static function rowsForUserVideo(int $userId, int $videoId): Collection
    {
        return self::where('user_id', $userId)
            ->where('video_id', $videoId)
            ->orderBy('id')
            ->get();
    }

    /**
     * Sum unique watched seconds, grouped per video (second index 5 on two videos = 2 seconds).
     */
    public static function sumWatchedSecondsFromRows(Collection $rows): int
    {
        $byVideo = [];

        foreach ($rows as $row) {
            $byVideo[$row->video_id] = array_merge(
                $byVideo[$row->video_id] ?? [],
                self::normalizeWatchedTimes($row->watched_times)
            );
        }

        $total = 0;
        foreach ($byVideo as $times) {
            $total += count(array_unique($times));
        }

        return $total;
    }

    public static function countUniqueSecondsFromRows(Collection $rows): int
    {
        return self::sumWatchedSecondsFromRows($rows);
    }

    public static function getMergedWatchedSeconds(int $userId, int $videoId): int
    {
        return self::sumWatchedSecondsFromRows(self::rowsForUserVideo($userId, $videoId));
    }

    public static function getAllWatchedTimes(int $userId, int $videoId): array
    {
        $all = [];

        foreach (self::rowsForUserVideo($userId, $videoId) as $row) {
            $all = array_merge($all, self::normalizeWatchedTimes($row->watched_times));
        }

        return array_values(array_unique($all));
    }

    public static function hasUserFullyWatchedVideo(int $userId, int $videoId, ?Video $video = null): bool
    {
        if (self::where('user_id', $userId)->where('video_id', $videoId)->where('watched', true)->exists()) {
            return true;
        }

        $video = $video ?? Video::find($videoId);
        if (! $video?->duration) {
            return false;
        }

        $required = self::requiredSecondCount($video);
        if ($required === 0) {
            return false;
        }

        return self::getMergedWatchedSeconds($userId, $videoId) >= $required;
    }

    /**
     * Total unique watched seconds in a time window.
     *
     * $timeMode:
     *   session  → filter by created_at (when the viewing session started — use for "10 days ago")
     *   activity → filter by updated_at (last heartbeat — use for "last 3 hours")
     */
    public static function getTotalWatchedSecondsInRange(
        int $userId,
        ?\DateTimeInterface $since = null,
        ?\DateTimeInterface $until = null,
        ?int $courseId = null,
        ?int $videoId = null,
        string $timeMode = self::TIME_SESSION_START,
    ): int {
        $query = self::where('user_id', $userId);
        $timeColumn = $timeMode === self::TIME_ACTIVITY ? 'updated_at' : 'created_at';

        if ($since) {
            $query->where($timeColumn, '>=', Carbon::parse($since));
        }
        if ($until) {
            $query->where($timeColumn, '<=', Carbon::parse($until));
        }
        if ($videoId) {
            $query->where('video_id', $videoId);
        } elseif ($courseId) {
            $ids = self::streamVideoIdsForCourse($courseId);
            if ($ids->isEmpty()) {
                return 0;
            }
            $query->whereIn('video_id', $ids);
        }

        return self::sumWatchedSecondsFromRows($query->get());
    }

    /** @return Collection<int, int> */
    public static function streamVideoIdsForCourse(int $courseId): Collection
    {
        $course = Course::with('section.episode.videos')->find($courseId);
        if (! $course) {
            return collect();
        }

        $ids = collect();
        foreach ($course->section as $section) {
            foreach ($section->episode->where('publish', 1) as $episode) {
                $stream = $episode->videos->where('type', 'stream')->first();
                if ($stream) {
                    $ids->push($stream->id);
                }
            }
        }

        return $ids->unique()->values();
    }

    /**
     * Named periods (hour/day/week/…) or pass $since/$until for custom ranges.
     */
    public static function getWatchedTimeForUserInCurrentPeriod(
        $userId,
        $period = 'all',
        $videoId = null,
        ?\DateTimeInterface $since = null,
        ?\DateTimeInterface $until = null,
        ?string $timeMode = null,
    ) {
        if ($since || $until) {
            return self::getTotalWatchedSecondsInRange(
                $userId,
                $since,
                $until,
                null,
                $videoId,
                $timeMode ?? self::TIME_SESSION_START,
            );
        }

        if ($period === 'all') {
            if ($videoId) {
                return self::getMergedWatchedSeconds($userId, $videoId);
            }

            return self::getTotalWatchedSecondsInRange($userId);
        }

        $startOfPeriod = match ($period) {
            'hour' => Carbon::now()->locale('fa')->startOfHour(),
            'day' => Carbon::now()->locale('fa')->startOfDay(),
            'week' => Carbon::now()->locale('fa')->startOfWeek(Carbon::SATURDAY),
            'month' => Carbon::now()->locale('fa')->startOfMonth(),
            'year' => Carbon::now()->locale('fa')->startOfYear(),
            default => throw new \InvalidArgumentException("Invalid period specified: {$period}"),
        };

        $mode = $timeMode ?? ($period === 'hour' ? self::TIME_ACTIVITY : self::TIME_SESSION_START);

        return self::getTotalWatchedSecondsInRange($userId, $startOfPeriod, now(), null, $videoId, $mode);
    }

    public static function getCourseProgressForUser($userId, $courseId, $period = 'all', ?\DateTimeInterface $since = null, ?\DateTimeInterface $until = null)
    {
        $course = Course::find($courseId);

        if (! $course) {
            throw new \Exception('Course not found');
        }

        $totalDuration = 0;
        $watchedDuration = 0;

        foreach ($course->section as $section) {
            foreach ($section->episode as $episode) {
                if ($episode->publish == 1) {
                    $video = $episode->videos()->where('type', 'stream')->first();

                    if ($video) {
                        $totalDuration += $video->duration;
                        $watchedDuration += self::getWatchedTimeForUserInCurrentPeriod(
                            $userId,
                            $period,
                            $video->id,
                            $since,
                            $until
                        );
                    }
                }
            }
        }

        $progressPercentage = $totalDuration > 0 ? ($watchedDuration / $totalDuration) * 100 : 0;

        return [
            'total_duration' => $totalDuration,
            'watched_duration' => $watchedDuration,
            'progress_percentage' => $progressPercentage,
        ];
    }

    public static function getEpisodeProgressForUser($userId, $episodeId, $period = 'all', ?\DateTimeInterface $since = null, ?\DateTimeInterface $until = null)
    {
        $episode = Episode::find($episodeId);
        if (! $episode) {
            throw new \Exception('Episode not found');
        }

        $video = $episode->videos()->where('type', 'stream')->first();
        if (! $video) {
            return [
                'video_duration' => 0,
                'watched_duration' => 0,
                'progress_percentage' => 0,
            ];
        }

        $videoDuration = $video->duration;
        $watchedTimeInSeconds = self::getWatchedTimeForUserInCurrentPeriod(
            $userId,
            $period,
            $video->id,
            $since,
            $until
        );
        $progressPercentage = $videoDuration > 0 ? ($watchedTimeInSeconds / $videoDuration) * 100 : 0;

        return [
            'video_duration' => $videoDuration,
            'watched_duration' => $watchedTimeInSeconds,
            'progress_percentage' => $progressPercentage,
        ];
    }

    public static function hasUserWatchedEpisode($userId, $episodeId)
    {
        $episode = Episode::find($episodeId);
        if (! $episode) {
            throw new \Exception('Episode not found');
        }

        $video = $episode->videos()->where('type', 'stream')->first();
        if (! $video) {
            return false;
        }

        return self::hasUserFullyWatchedVideo($userId, $video->id, $video);
    }

    public static function getWatchedChartForUser($userId, $days = 7)
    {
        $startDate = now()->subDays($days)->startOfDay();

        $videoViews = self::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->get();

        $watchedPerDay = [];

        foreach ($videoViews as $videoView) {
            // Each session row belongs to the day it started (created_at).
            $date = Carbon::parse($videoView->created_at)->toDateString();
            $vid = $videoView->video_id;

            if (! isset($watchedPerDay[$date][$vid])) {
                $watchedPerDay[$date][$vid] = [];
            }

            $watchedPerDay[$date][$vid] = array_merge(
                $watchedPerDay[$date][$vid],
                self::normalizeWatchedTimes($videoView->watched_times)
            );
        }

        $dailyTotals = [];
        foreach ($watchedPerDay as $date => $videos) {
            $dayTotal = 0;

            foreach ($videos as $times) {
                $dayTotal += count(array_unique($times));
            }

            $dailyTotals[$date] = $dayTotal;
        }

        $labels = [];
        $data = [];

        $period = \Carbon\CarbonPeriod::create($startDate, now()->endOfDay());
        foreach ($period as $date) {
            $d = $date->toDateString();
            $labels[] = $d;
            $data[] = $dailyTotals[$d] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}
