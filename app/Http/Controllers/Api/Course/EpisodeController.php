<?php

namespace App\Http\Controllers\Api\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Quiz\Quiz;
use App\Models\VideoView;
use App\Models\View;
use App\Services\Course\CourseAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class EpisodeController extends Controller
{
    public function getEpisode($course, $episode)
    {
        if (!$course || $course->publish == 0 || !$episode || $episode->publish == 0 || $course->id != $episode->section->course->id) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $user = auth('api')->user();
        $availabilityService = app(CourseAvailabilityService::class);
        $playBlock = $availabilityService->assertEpisodeViewable($episode, $user);

        if ($playBlock) {
            $courseBrief = Course::with([
                'status',
                'teacher' => fn ($q) => $q->select('id', 'first_name', 'last_name', 'username', 'profile_pic')->with('info'),
                'section.episode' => fn ($q) => $q->where('publish', 1),
            ])->find($course->id);
            $availabilityService->enrichCourseTree($courseBrief);

            $episode->load('attachs');
            $episodePayload = $episode->only([
                'id',
                'title',
                'slug',
                'english_title',
                'description',
                'section_id',
                'total_time',
                'created_at',
                'updated_at',
            ]);
            $episodePayload['attachs'] = $episode->attachs;

            return response()->json([
                'error' => $playBlock['error'],
                'code' => $playBlock['code'],
                'message' => $playBlock['message'],
                'available_at' => $playBlock['available_at'],
                'course' => $courseBrief,
                'episode' => $episodePayload,
                'course_availability' => $courseBrief->availability,
            ], 403);
        }

        $user = auth('api')->user();

        $userCanSeeCourse = false;
        if ($user) {
            $userCanSeeCourse = app(CourseAvailabilityService::class)->userHasCourseAccess($user, $course);
        }

        $course = Course::where('id', $course->id)
            ->with([
                'teacher' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'username', 'profile_pic')->with('info');
                },
                'section' => function ($query) {
                    $query->select('*')->with([
                        'episode' => function ($query) {
                            $query->where('publish', 1);
                        }
                    ]);
                },
                'status'
            ])
            ->withCount('users')
            ->first();

        // اضافه کردن درصد مشاهده برای هر اپیزود توی سکشن‌ها
        if ($user) {
            foreach ($course->section as $section) {
                foreach ($section->episode as $episodeItem) {
                    $progress = VideoView::getEpisodeProgressForUser($user->id, $episodeItem->id);
                    $episodeItem->progressPercentage = $progress['progress_percentage'] ?? 0;

                    $episodeItem->fullWatched = VideoView::hasUserWatchedEpisode($user->id, $episodeItem->id);
                }
            }
        }

        // Do not eager-load videos to avoid leaking unnecessary info to client
        $episode = Episode::where('id', $episode->id)->with('attachs')->first();

        View::createFor($episode);

        // Get video processing status without hydrating relation on the model
        $rawVideo = $episode->videos()->where('type', 'raw')->first();
        $streamVideo = $episode->videos()->where('type', 'stream')->first();
        
        $videoStatus = null;
        $videoId = null;
        $isVideoProcessed = false;
        
        if ($rawVideo) {
            $videoStatus = $rawVideo->status ?? 'queued';
            $videoId = $rawVideo->id;
            $isVideoProcessed = $videoStatus === 'processed';
        }
        
        // Add video processing info to episode
        $episode->video_status = $videoStatus;
        $episode->video_id = $videoId;
        $episode->is_video_processed = $isVideoProcessed;
        // Expose only the stream video id for frontend tracking without leaking full videos relation
        $episode->stream_video_id = $streamVideo?->id;

        // Map attachments to minimal payload: title, size (bytes), url
        if ($episode->relationLoaded('attachs') && $episode->attachs) {
            $mappedAttachs = $episode->attachs->map(function ($att) {
                try {
                    $url = (string) ($att->url ?? '');
                    $title = $att->title ?? (basename(parse_url($url, PHP_URL_PATH) ?? '') ?: null);
                    $size = null;
                    if (!empty($url)) {
                        try {
                            $head = Http::withHeaders(['Accept' => '*/*'])->head($url);
                            $len = $head->header('Content-Length');
                            if (is_numeric($len)) {
                                $size = (int) $len;
                            }
                        } catch (\Throwable $e) { /* ignore */ }
                    }
                    return [
                        'title' => $title,
                        'size' => $size,
                        'url' => $url ?: null,
                    ];
                } catch (\Throwable $e) {
                    return [
                        'title' => $att->title ?? null,
                        'size' => null,
                        'url' => (string) ($att->url ?? ''),
                    ];
                }
            })->values();

            $episode->setRelation('attachs', $mappedAttachs);
        }

        // Compute can_download according to business rules
        $canDownload = false;
        if ($user) {
            $canDownload = $user->canDownloadCourse($course);
        } else {
            // Login required for any download
            $canDownload = false;
        }

        // If user is not allowed to download, hide attachment URLs
        if (!$canDownload && $episode->relationLoaded('attachs') && $episode->attachs) {
            $episode->setRelation('attachs', $episode->attachs->map(function ($att) {
                if (is_array($att)) {
                    $att['url'] = null;
                    return $att;
                }
                // Fallback if unexpected type
                return [
                    'title' => $att['title'] ?? null,
                    'size' => $att['size'] ?? null,
                    'url' => null,
                ];
            })->values());
        }

        if ($user) {
            // پیشرفت کاربر در دوره
            $progress = VideoView::getCourseProgressForUser($user->id, $course->id, 'all');
            $course->totalDuration = $progress['total_duration'];
            $course->watchedDuration = $progress['watched_duration'];
            $course->progressPercentage = $progress['progress_percentage'];

            // پیشرفت کاربر در اپیزود جاری
            $episodeProgress = VideoView::getEpisodeProgressForUser($user->id, $episode->id);
            $episode->progressPercentage = $episodeProgress['progress_percentage'] ?? 0;

            // بررسی وضعیت تماشای ویدیو
            $episode_video = $episode->videos()->where('type', 'stream')->first();
            
            if ($episode_video) {
                $videoViews = $user->videoViews()->where('video_id', $episode_video->id)->get();

                if ($videoViews->isNotEmpty()) {
                    $allWatchedTimes = [];
                    $lastPosition = null;
                    $fullWatched = 0;

                    foreach ($videoViews as $view) {
                        $watchedTimes = json_decode($view->watched_times, true);
                        if ($watchedTimes) {
                            $allWatchedTimes = array_merge($allWatchedTimes, $watchedTimes);
                        }
                        $lastPosition = $view->last_position;
                        $fullWatched = $view->watched;
                    }

                    $allWatchedTimes = array_unique($allWatchedTimes);
                    sort($allWatchedTimes);

                    $episode['watchedTimes'] = json_encode($allWatchedTimes);
                    $episode['lastPosition'] = $lastPosition;
                    $episode['watched'] = $fullWatched;
                }
            } else {
                // Set default values when video is not processed yet
                $episode['watchedTimes'] = json_encode([]);
                $episode['lastPosition'] = 0;
                $episode['watched'] = false;
            }
        }

        // اطلاعات گواهی و لایک و بوکمارک و ...
        $certificate = $user?->certificates()
            ->where('course_id', $course->id)
            ->where('status', 'issued')
            ->first();
        $userCompletedCourse = (bool) $certificate;
        $certificateUuid = $certificate?->uuid ?? null;
        $userFullyWatchedCourse = $user
            ? ($course->hasStoredCompletionForUser($user->id) || $course->isCompletedByUser($user->id))
            : false;
        $likesCount = $episode->likes()->count();
        $userHasLiked = $user ? $user->hasLiked($episode) : false;
        $commentsCount = $episode->comments()->where('approved', '1')->count();
        $bookmarksCount = $episode->bookmarkersCount();
        $userHasBookmarked = $user ? $user->hasBookmarked($episode) : false;

        // محاسبه امتیازها
        $ratings = [
            'countOfOne' => $course->sumOfRateNumber(1),
            'countOfTwo' => $course->sumOfRateNumber(2),
            'countOfThree' => $course->sumOfRateNumber(3),
            'countOfFour' => $course->sumOfRateNumber(4),
            'countOfFive' => $course->sumOfRateNumber(5),
            'countOfAll' => $course->sumOfAllRate(),
            'sumOfAll' => $course->sumRating(),
            'averageRating' => $course->averageRating(),
            'currentUserRate' => ($user && $course->checkUserRateThis($user->id)) ? $course->checkUserRateThis($user->id)->only('rating', 'comment') : null,
        ];
        $course->ratings = $ratings;

        app(CourseAvailabilityService::class)->enrichCourseTree($course);
        $episodeAvailability = app(CourseAvailabilityService::class)->episodeAvailability($episode);
        $episode->setAttribute('availability', $episodeAvailability);
        $episode->setAttribute('is_playable', $episodeAvailability['is_playable']);

        return response()->json([
            'message' => 'Success',
            'userCanSeeCourse' => $userCanSeeCourse,
            'userCompletedCourse' => $userCompletedCourse,
            'userFullyWatchedCourse' => $userFullyWatchedCourse,
            'certificateUuid' => $certificateUuid,
            'course_availability' => $course->availability,
            'course' => $course,
            'episode' => $episode,
            'quizzes' => $this->quizzesFor(Episode::class, $episode->id),
            'can_download' => $canDownload,
            'comments_count' => $commentsCount,
            'likes_count' => $likesCount,
            'user_has_liked' => $userHasLiked,
            'bookmarks_count' => $bookmarksCount,
            'user_has_bookmarked' => $userHasBookmarked,
        ], 200);
    }


    public function episodeDownloadCheck(Episode $episode, Request $request)
    {
        try {
            $downloads = $episode->videos->where('type', 'download');

            if ($downloads->isEmpty()) {
                return response()->json(['message' => 'File not found'], 404);
            }

            $course = $episode->section->course;
            $playBlock = app(CourseAvailabilityService::class)->assertEpisodePlayable($episode);
            if ($playBlock) {
                return response()->json([
                    'message' => $playBlock['message'],
                    'code' => $playBlock['code'],
                    'available_at' => $playBlock['available_at'],
                ], 403);
            }

            $user = auth('api')->user();
            if (!$user || !$user->canDownloadCourse($course)) {
                return response()->json(['message' => 'You do not have access to download this file'], 403);
            }

            // If a specific quality is requested, validate and return a signed URL for that quality
            if ($request->filled('quality')) {
                $quality = (int) $request->input('quality');
                $video = $downloads->firstWhere('quality', $quality);
                if (!$video) {
                    return response()->json(['message' => 'Requested quality not available'], 404);
                }

                $disk = $video->disk;
                $path = parse_url($video->path, PHP_URL_PATH);
                if (!Storage::disk($disk)->exists($path)) {
                    return response()->json(['message' => 'File not found'], 404);
                }

                $url = URL::temporarySignedRoute('api.episode-download', now()->addMinutes(20), [
                    'episode' => $episode->id,
                    'quality' => $quality,
                ]);

                return response()->json(['message' => 'Success', 'url' => $url]);
            }

            // Otherwise, return the list of available qualities with sizes (bytes)
            $qualityEntries = $downloads->map(function ($v) {
                return [
                    'quality' => (int) $v->quality,
                    'disk' => $v->disk,
                    'path' => parse_url($v->path, PHP_URL_PATH),
                ];
            })->filter(function ($item) {
                return Storage::disk($item['disk'])->exists($item['path']);
            })->map(function ($item) {
                return [
                    'quality' => $item['quality'],
                    'size' => Storage::disk($item['disk'])->size($item['path']),
                ];
            })->unique('quality')->values()->all();

            if (empty($qualityEntries)) {
                return response()->json(['message' => 'File not found'], 404);
            }

            // Sort ascending by quality; client may reverse if needed
            usort($qualityEntries, function ($a, $b) { return $a['quality'] <=> $b['quality']; });
            return response()->json(['message' => 'Success', 'qualities' => $qualityEntries]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while processing your request', 'error' => $e->getMessage()], 500);
        }
    }

    public function episodeDownload(Episode $episode, Request $request)
    {
        try {
            $downloads = $episode->videos->where('type', 'download');
            if ($downloads->isEmpty()) {
                return response()->json(['message' => 'File not found'], 404);
            }

            $video = null;
            if ($request->filled('quality')) {
                $quality = (int) $request->input('quality');
                $video = $downloads->firstWhere('quality', $quality);
                if (!$video) {
                    return response()->json(['message' => 'Requested quality not available'], 404);
                }
            } else {
                // fallback to the first available download if no quality specified
                $video = $downloads->first();
            }

            $disk = $video->disk;
            $path = parse_url($video->path, PHP_URL_PATH);

            if (!Storage::disk($disk)->exists($path)) {
                return response()->json(['message' => 'File not found'], 404);
            }

            return Storage::disk($disk)->download($path);

        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while processing your request', 'error' => $e->getMessage()], 500);
        }
    }

    protected function quizzesFor(string $type, int $id): array
    {
        return Quiz::availableNow()
            ->where('quizzable_type', $type)
            ->where('quizzable_id', $id)
            ->orderBy('id')
            ->get()
            ->map(fn (Quiz $quiz) => $quiz->toStudentSummary())
            ->values()
            ->all();
    }


}
