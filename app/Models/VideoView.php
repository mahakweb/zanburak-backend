<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoView extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'video_id', 'watched_times', 'last_position', 'watched'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function video()
    {
        return $this->belongsTo(Video::class);
    }

    public static function getWatchedTimeForUserInCurrentPeriod($userId, $period = 'all', $videoId = null)
    {
        $startOfPeriod = null;

        if ($period !== 'all') {
            switch ($period) {
                case 'hour':
                    $startOfPeriod = Carbon::now()->locale('fa')->startOfHour();
                    break;
                case 'day':
                    $startOfPeriod = Carbon::now()->locale('fa')->startOfDay();
                    break;
                case 'week':
                    $startOfPeriod = Carbon::now()->locale('fa')->startOfWeek(Carbon::SATURDAY);
                    break;
                case 'month':
                    $startOfPeriod = Carbon::now()->locale('fa')->startOfMonth();
                    break;
                case 'year':
                    $startOfPeriod = Carbon::now()->locale('fa')->startOfYear();
                    break;
                default:
                    throw new \InvalidArgumentException("Invalid period specified.");
            }
        }

        // بازیابی تمام رکوردهای مربوط به یک کاربر، با یا بدون محدودیت زمانی
        $query = self::where('user_id', $userId);
        if ($videoId) {
            $query->where('video_id', $videoId);
        }
        if ($startOfPeriod) {
            $query->where('updated_at', '>=', $startOfPeriod);
        }
        $videoViews = $query->get();

        $watchedTimeByVideo = [];

        foreach ($videoViews as $videoView) {
            $videoId = $videoView->video_id;
            $watchedTimes = json_decode($videoView->watched_times, true);

            if (!isset($watchedTimeByVideo[$videoId])) {
                $watchedTimeByVideo[$videoId] = [];
            }

            if ($watchedTimes) {
                $watchedTimeByVideo[$videoId] = array_merge($watchedTimeByVideo[$videoId], $watchedTimes);
            }
        }

        $totalWatchedTimes = 0;

        foreach ($watchedTimeByVideo as $videoId => $times) {
            $uniqueTimes = array_unique($times);
            $totalWatchedTimes += count($uniqueTimes);
        }

        return $totalWatchedTimes;
    }

    public static function getCourseProgressForUser($userId, $courseId, $period = 'all')
    {
        $course = Course::find($courseId);

        if (!$course) {
            throw new \Exception("Course not found");
        }

        $totalDuration = 0;
        $watchedDuration = 0;

        foreach ($course->section as $section) {
            foreach ($section->episode as $episode) {
                if ($episode->publish == 1) {
                    $video = $episode->videos()->where('type', 'stream')->first();

                    if ($video) {
                        $totalDuration += $video->duration;

                        $watchedTimeInSeconds = self::getWatchedTimeForUserInCurrentPeriod($userId, $period, $video->id);
                        $watchedDuration += $watchedTimeInSeconds;
                    }
                    // Skip episodes without stream videos (not processed yet)
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

    public static function getEpisodeProgressForUser($userId, $episodeId, $period = 'all')
    {
        $episode = Episode::find($episodeId);
        if (!$episode) {
            throw new \Exception("Episode not found");
        }

        $video = $episode->videos()->where('type', 'stream')->first();
        if (!$video) {
            // Return default values when video is not processed yet
            return [
                'video_duration' => 0,
                'watched_duration' => 0,
                'progress_percentage' => 0,
            ];
        }

        $videoDuration = $video->duration;
        $watchedTimeInSeconds = self::getWatchedTimeForUserInCurrentPeriod($userId, $period, $video->id);

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
        if (!$episode) {
            throw new \Exception("Episode not found");
        }

        $video = $episode->videos()->where('type', 'stream')->first();
        if (!$video) {
            // Return false when video is not processed yet
            return false;
        }

        $watched = VideoView::where('user_id', $userId)
            ->where('video_id', $video->id)
            ->orderByDesc('id')
            ->value('watched'); // فقط مقدار watched رو میاره


        // $hasWatched = VideoView::where('user_id', $userId)
        //     ->where('video_id', $video->id)
        //     ->where('watched', true)
        //     ->exists(); // فقط چک میکنه آیا همچین رکوردی وجود داره یا نه

        return (bool) $watched;
    }

    public static function getWatchedChartForUser($userId, $days = 7)
    {
        $startDate = now()->subDays($days)->startOfDay();

        $videoViews = self::where('user_id', $userId)
            ->where('updated_at', '>=', $startDate)
            ->get();

        $watchedPerDay = [];

        foreach ($videoViews as $videoView) {
            $date = Carbon::parse($videoView->updated_at)->toDateString();
            $videoId = $videoView->video_id;

            if (!isset($watchedPerDay[$date][$videoId])) {
                $watchedPerDay[$date][$videoId] = [];
            }

            $watchedTimes = json_decode($videoView->watched_times, true) ?? [];

            $watchedPerDay[$date][$videoId] = array_merge(
                $watchedPerDay[$date][$videoId],
                $watchedTimes
            );
        }

        $dailyTotals = [];
        foreach ($watchedPerDay as $date => $videos) {
            $dayTotal = 0;

            foreach ($videos as $videoId => $times) {
                $uniqueTimes = array_unique($times);
                $dayTotal += count($uniqueTimes);
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
