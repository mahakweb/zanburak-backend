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
            if($course->isCompletedByUser($user->id) && $course->end_date) {
                $user->courses()->updateExistingPivot($course->id, [
                    'completed_at' => now(),
                ]);

                $uuid = '';
                do {
                    $uuid = Str::uuid();
                } while (Certificate::where('uuid', $uuid)->exists());

                $user->certificates()->create([
                    'course_id' => $course->id,
                    'uuid' => $uuid,
                    'user_name' => $user->first_name.' '.$user->last_name,
                    'course_title' => $course->title,
                    'time_completed' => $course->totalTime(),
                    'issued_at' => now(),
                ]);
            }
        }

        return response()->json(['message' => 'Success'], 200);
    }

}
