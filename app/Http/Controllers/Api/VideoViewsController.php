<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Episode;
use App\Models\Video;
use App\Models\VideoView;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class VideoViewsController extends Controller
{
    public function getWatched(Request $request, Video $video)
    {
        dd($video);
    }

    public function setWatched(Request $request, Video $video)
    {
        $user = auth('api')->user();
        $watchedTimes = json_decode($request->tempWatchedTimes, true);
        $last_position = $request->lastPosition;
        $full_watched = $request->fullWatched;

        $oneHourAgo = now()->subHour();

        $videoView = VideoView::where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('updated_at', '>=', $oneHourAgo)
            ->first();

        if ($videoView) {
            $existingWatchedTimes = json_decode($videoView->watched_times, true);

            $mergedWatchedTimes = array_values(array_unique(array_merge($existingWatchedTimes, $watchedTimes)));

            $videoView->update([
                'watched_times' => $mergedWatchedTimes,
                'last_position' => $last_position,
                'watched' => $full_watched,
            ]);
        } else {
            VideoView::create([
                'user_id' => $user->id,
                'video_id' => $video->id,
                'watched_times' => json_encode($watchedTimes),
                'last_position' => $last_position,
                'watched' => $full_watched,
            ]);
        }

        if (
            $full_watched && $video->videoable_type == 'App\Models\Episode') {
            $episode = Episode::find($video->videoable_id);
            $course = $episode->section->course;
            
            // Fire event for episode fully watched (only once)
            $wasFullyWatchedBefore = $video->watched;
            if (!$wasFullyWatchedBefore) {
                event(new \App\Events\Score\Episode\EpisodeFullyWatched($user, $episode));
            }
            
            // محاسبه پیشرفت دوره
            $progress = \App\Models\VideoView::getCourseProgressForUser($user->id, $course->id);
            $progressPercentage = (int) round($progress['progress_percentage']);
            
            // ارسال notification پیشرفت (برای 25%, 50%, 75% و 70%+)
            if (in_array($progressPercentage, [25, 50, 75]) || ($progressPercentage >= 70 && $progressPercentage < 100)) {
                event(new \App\Events\Course\CourseProgress($user, $course, $progressPercentage));
            }
            
            // ارسال notification نزدیک به اتمام (90%+)
            if ($progressPercentage >= 90 && $progressPercentage < 100) {
                $remainingPercent = 100 - $progressPercentage;
                event(new \App\Events\Course\CourseNearCompletion($user, $course, $remainingPercent));
            }
            
            // بررسی تکمیل دوره و صدور گواهینامه
            if ($course->certificate_enabled
                && $course->isCompletedByUser($user->id)
                && $course->end_date
                && $course->end_date <= now()
            ) {
                $wasCompleted = $user->courses()->where('course_id', $course->id)->whereNotNull('completed_at')->exists();

                $user->courses()->updateExistingPivot($course->id, [
                    'completed_at' => now(),
                ]);

                $certificate = app(\App\Services\Certificate\CertificateIssuanceService::class)
                    ->issueForCourse($user, $course);

                if ($certificate && ! $wasCompleted) {
                    event(new \App\Events\Course\CourseCompleted($user, $course));
                    event(new \App\Events\Mission\CourseCompletionEvent($user, $course, now()));
                }

                if ($certificate) {
                    event(new \App\Events\Course\CertificateIssued($user, $course, $certificate));
                }
            }
        }

        return response()->json(['message' => 'Success'], 200);
    }

}
