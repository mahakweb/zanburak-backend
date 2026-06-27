<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Episode;
use App\Models\Video;
use App\Models\VideoView;
use App\Services\Certificate\CertificateEligibilityService;
use App\Services\Certificate\CertificateIssuanceService;
use App\Services\Course\CourseAvailabilityService;
use App\Services\Course\CourseWatchCompletionService;
use Illuminate\Http\Request;

class VideoViewsController extends Controller
{
    public function getWatched(Request $request, Video $video)
    {
        $user = auth('api')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $latestRow = VideoView::where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'watchedTimes' => VideoView::getAllWatchedTimes($user->id, $video->id),
            'lastPosition' => $latestRow?->last_position ?? 0,
            'fullWatched' => VideoView::hasUserFullyWatchedVideo($user->id, $video->id, $video),
            'watchedSeconds' => VideoView::getMergedWatchedSeconds($user->id, $video->id),
            'requiredSeconds' => VideoView::requiredSecondCount($video),
            'missingSeconds' => VideoView::getMissingSecondIndices($user->id, $video->id, $video),
            'coveragePercent' => VideoView::getWatchCoveragePercent($user->id, $video->id, $video),
        ]);
    }

    public function setWatched(Request $request, Video $video)
    {
        $user = auth('api')->user();
        $incomingTimes = json_decode($request->tempWatchedTimes, true) ?? [];
        $lastPosition = (float) $request->lastPosition;
        $fullWatched = filter_var($request->fullWatched, FILTER_VALIDATE_BOOLEAN);

        if ($video->videoable_type === Episode::class || $video->videoable_type === 'App\Models\Episode') {
            $episode = Episode::with('section.course')->find($video->videoable_id);
            if (! $episode?->section?->course) {
                return response()->json(['message' => 'Course not found'], 404);
            }
            if (! app(CourseAvailabilityService::class)->userHasCourseAccess($user, $episode->section->course)) {
                return response()->json(['message' => 'Access denied'], 403);
            }
            $playBlock = app(CourseAvailabilityService::class)->assertEpisodePlayable($episode);
            if ($playBlock) {
                return response()->json([
                    'error' => $playBlock['error'],
                    'code' => $playBlock['code'],
                    'message' => $playBlock['message'],
                    'available_at' => $playBlock['available_at'],
                ], 403);
            }
        }

        $wasFullyWatchedBefore = VideoView::hasUserFullyWatchedVideo($user->id, $video->id, $video);

        // Each viewing session = its own row. Merge only within the same session
        // (same calendar day + no inactivity gap). Never touch older session rows.
        $videoView = VideoView::findActiveSession($user->id, $video->id);

        if ($videoView) {
            $sessionTimes = self::uniqueSecondIndices(array_merge(
                self::normalizeWatchedTimes($videoView->watched_times),
                $incomingTimes
            ));

            $videoView->update([
                'watched_times' => $sessionTimes,
                'last_position' => $lastPosition,
                'watched' => $videoView->watched || $fullWatched,
            ]);
        } else {
            VideoView::create([
                'user_id' => $user->id,
                'video_id' => $video->id,
                'watched_times' => self::uniqueSecondIndices($incomingTimes),
                'last_position' => $lastPosition,
                'watched' => $fullWatched,
            ]);
        }

        $nowFullyWatched = VideoView::hasUserFullyWatchedVideo($user->id, $video->id, $video);

        $isEpisode = $video->videoable_type === Episode::class
            || $video->videoable_type === 'App\Models\Episode';

        if ($isEpisode) {
            $episode = $episode ?? Episode::find($video->videoable_id);
            $course = $episode->section->course;

            if ($nowFullyWatched && ! $wasFullyWatchedBefore) {
                event(new \App\Events\Score\Episode\EpisodeFullyWatched($user, $episode));
            }

            $newlyCompleted = app(CourseWatchCompletionService::class)->markIfFullyWatched($user, $course);

            if ($nowFullyWatched) {
                $progress = VideoView::getCourseProgressForUser($user->id, $course->id);
                $progressPercentage = (int) round($progress['progress_percentage']);

                if (in_array($progressPercentage, [25, 50, 75]) || ($progressPercentage >= 70 && $progressPercentage < 100)) {
                    event(new \App\Events\Course\CourseProgress($user, $course, $progressPercentage));
                }

                if ($progressPercentage >= 90 && $progressPercentage < 100) {
                    $remainingPercent = 100 - $progressPercentage;
                    event(new \App\Events\Course\CourseNearCompletion($user, $course, $remainingPercent));
                }
            }

            if ($newlyCompleted || $nowFullyWatched) {
                $eligibility = app(CertificateEligibilityService::class);
                if ($eligibility->canAutoIssue($user, $course)) {
                    $eligibility->markCourseCompleted($user, $course);

                    $certificate = app(CertificateIssuanceService::class)->issueForCourse($user, $course);

                    if ($certificate) {
                        event(new \App\Events\Course\CertificateIssued($user, $course, $certificate));
                    }
                }
            }
        }

        return response()->json(['message' => 'Success'], 200);
    }
}
