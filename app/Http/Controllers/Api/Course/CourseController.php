<?php

namespace App\Http\Controllers\Api\Course;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Quiz\Quiz;
use App\Models\Section;
use App\Models\Path;
use App\Models\Status;
use App\Models\VideoView;
use App\Models\View;
use App\Services\Course\CourseAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    public function filters()
    {
        $categories = Category::all();
        $statuses = Status::where('english_title', '!=', 'archive')->get();
        $numberOfFreeCourse = Course::where('type', 'free')->where('publish', 1)->count();
        $numberOfCashCourse = Course::where('type', 'cash')->where('publish', 1)->count();
        $numberOfCashvipCourse = Course::where('type', 'cash-vip')->where('publish', 1)->count();
        return response()->json(['message' => 'Success', 'categories' => $categories, 'statuses' => $statuses, 'numberOfFreeCourse' => $numberOfFreeCourse, 'numberOfCashCourse' => $numberOfCashCourse, 'numberOfCashvipCourse' => $numberOfCashvipCourse], 200);
    }

    public function courses(Request $request)
    {
        $user = auth('api')->user();
        $cat = $request->input('cat', []);
        $type = $request->input('type', []);
        $status = $request->input('status', []);
        $order = $request->input('order', 'newest');

        $query = Course::where('publish', '1')
            ->notArchived()
            ->with('status:id,title,english_title,slug')
            ->cat($cat)
            ->type($type)
            ->status($status)
            ->order($order);

        $availability = app(CourseAvailabilityService::class);

        $allCourses = $query->get();

        $courses = $allCourses->map(function ($course) use ($user, $availability) {
            $teacher = $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic');
            $totalTime = $course->totalTime();
            $likesCount = $course->likes()->count();
            $userHasLiked = $user ? $user->hasLiked($course) : false;
            $listMeta = $availability->listItemMeta($course);

            return [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'price' => $course->price,
                'poster' => $course->poster,
                'description' => $course->description,
                'short_description' => $course->short_description,
                'avgRating' => $course->averageRating(),
                'total_time' => $totalTime,
                'likes_count' => $likesCount,
                'user_has_liked' => $userHasLiked,
                'teacher' => $teacher,
                'is_presale' => $listMeta['is_presale'],
                'is_archive' => $listMeta['is_archive'],
                'is_purchasable' => $listMeta['is_purchasable'],
                'can_watch_videos' => $listMeta['can_watch_videos'],
                'start_date' => $listMeta['start_date'],
                'status' => $listMeta['status'],
                'last_content_update' => $listMeta['last_content_update'],
            ];
        });

        $coursesPerPage = $request->input('perPage', 12);
        $currentPage = $request->input('page', 1);
        $total = $courses->count();
        $lastPage = ceil($total / $coursesPerPage);
        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedCourses = $courses->slice(($currentPage - 1) * $coursesPerPage, $coursesPerPage)->values();

        // $paths = Path::withCount('courses')->get();

        return response()->json([
            'message' => 'Success',
            // 'paths' => $paths,
            'courses' => $paginatedCourses,
            'pagination' => [
                'total' => $total,
                'current_page' => intval($currentPage),
                'per_page' => $coursesPerPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage
            ]
        ], 200);
    }

    public function getCourse($course)
    {
        if (!$course || $course->publish == 0) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $user = auth('api')->user();

        View::createFor($course);

        $relatedCourses = $course->category
            ->flatMap(fn($category) => $category->course)
            ->where('id', '!=', $course->id)
            ->where('publish', 1)
            ->filter(fn ($c) => $c->status?->english_title !== 'archive')
            ->unique('id')
            ->map->only(['id', 'slug', 'title', 'english_title']);

        $course = Course::with([
            'paths',
            'teacher:id,first_name,last_name,username,profile_pic',
            'teacher.info',
            'section.episode' => fn($q) => $q->where('publish', 1)->with('attachs'),
            'status'
        ])
            ->withCount('users')
            ->findOrFail($course->id);

        $episode_number = 1;

        foreach ($course->section as $section) {
            foreach ($section->episode as $episodeItem) {
                $episodeItem->number = $episode_number++;

                if ($user) {
                    $progress = VideoView::getEpisodeProgressForUser($user->id, $episodeItem->id);
                    $episodeItem->progressPercentage = $progress['progress_percentage'] ?? 0;
                    $episodeItem->fullWatched = VideoView::hasUserWatchedEpisode($user->id, $episodeItem->id);
                }
            }
        }

        $sectionIds = $course->section->pluck('id');
        $sectionQuizMap = Quiz::availableNow()
            ->where('quizzable_type', Section::class)
            ->whereIn('quizzable_id', $sectionIds)
            ->get()
            ->groupBy('quizzable_id');

        foreach ($course->section as $section) {
            $section->setAttribute(
                'quizzes',
                ($sectionQuizMap->get($section->id) ?? collect())
                    ->map(fn (Quiz $quiz) => $quiz->toStudentSummary())
                    ->values()
                    ->all()
            );
        }

        $userCanSeeCourse = app(CourseAvailabilityService::class)->userHasCourseAccess($user, $course);
        $availabilityService = app(CourseAvailabilityService::class);
        // Login required for any download
        $canDownload = $user && $availabilityService->userHasCourseAccess($user, $course)
            ? $user->canDownloadCourse($course)
            : false;

        $certificate = $user?->certificates()
            ->where('course_id', $course->id)
            ->where('status', 'issued')
            ->first();
        $userCompletedCourse = (bool) $certificate;
        $certificateUuid = $certificate?->uuid ?? null;

        $commentsCount = $course->comments()->where('approved', '1')->count();

        $likesCount = $course->likes()->count();
        $userHasLiked = $user ? $user->hasLiked($course) : false;

        $bookmarksCount = $course->bookmarkersCount();
        $userHasBookmarked = $user ? $user->hasBookmarked($course) : false;

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

        // Trailer metadata and fallback similar to episode handling
        try {
            // Query via relation builder to avoid hydrating and serializing the videos relation
            $trailerRecord = $course->videos()->where('type', 'trailer')->first(); // may be HLS master or MP4
            $rawTrailer = $course->videos()->where('type', 'raw')->first();    // Raw uploaded file

            $isHlsTrailer = $trailerRecord && str_contains($trailerRecord->path, '.m3u8');

            // Prefer HLS presence to mark as processed
            if ($isHlsTrailer) {
                $course->trailer_status = 'processed';
                $course->trailer_video_id = $trailerRecord->id;
                $course->has_stream_trailer = true;
            } else {
                $course->has_stream_trailer = false;

                // If there is a non-HLS trailer record (e.g., mp4), treat it as the direct trailer
                $directTrailer = null;
                if ($trailerRecord && !str_contains($trailerRecord->path, '.m3u8')) {
                    $directTrailer = $trailerRecord;
                } elseif ($rawTrailer) {
                    $directTrailer = $rawTrailer;
                }

                if ($directTrailer) {
                    $course->trailer_status = $directTrailer->status ?? 'uploaded';
                    $course->trailer_video_id = $directTrailer->id;

                    // Populate course->trailer with an accessible URL so frontend can play MP4 directly
                    $diskUrl = config("filesystems.disks.{$directTrailer->disk}.url");
                    if (!empty($diskUrl)) {
                        $course->trailer = rtrim($diskUrl, '/') . '/' . ltrim($directTrailer->path, '/');
                    } else {
                        $course->trailer = $directTrailer->path;
                    }
                }
            }

            // If still empty and there is a raw trailer, expose it as a last resort
            if (empty($course->trailer) && $rawTrailer) {
                $diskUrl = config("filesystems.disks.{$rawTrailer->disk}.url");
                if (!empty($diskUrl)) {
                    $course->trailer = rtrim($diskUrl, '/') . '/' . ltrim($rawTrailer->path, '/');
                } else {
                    $course->trailer = $rawTrailer->path;
                }
            }
        } catch (\Throwable $e) {
            // noop
        }


        $userFullyWatchedCourse = $user
            ? ($course->hasStoredCompletionForUser($user->id) || $course->isCompletedByUser($user->id))
            : false;

        app(CourseAvailabilityService::class)->enrichCourseTree($course);
        $courseAvailability = $course->availability;

        return response()->json([
            'message' => 'Success',
            'userCanSeeCourse' => $userCanSeeCourse,
            'userCompletedCourse' => $userCompletedCourse,
            'userFullyWatchedCourse' => $userFullyWatchedCourse,
            'certificateUuid' => $certificateUuid,
            'course_availability' => $courseAvailability,
            'relatedCourses' => $relatedCourses,
            'course' => $course,
            'quizzes' => $this->allQuizzesForCourse($course),
            'course_quizzes' => $this->quizzesFor(Course::class, $course->id),
            'can_download' => $canDownload,
            'comments_count' => $commentsCount,
            'likes_count' => $likesCount,
            'user_has_liked' => $userHasLiked,
            'bookmarks_count' => $bookmarksCount,
            'user_has_bookmarked' => $userHasBookmarked,
        ], 200);
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

    protected function allQuizzesForCourse(Course $course): array
    {
        $sectionIds = $course->section()->pluck('id');
        $episodeIds = Episode::query()
            ->whereIn('section_id', $sectionIds)
            ->pluck('id');

        return Quiz::availableNow()
            ->where(function ($query) use ($course, $sectionIds, $episodeIds) {
                $query->where(function ($q) use ($course) {
                    $q->where('quizzable_type', Course::class)
                        ->where('quizzable_id', $course->id);
                })->orWhere(function ($q) use ($sectionIds) {
                    $q->where('quizzable_type', Section::class)
                        ->whereIn('quizzable_id', $sectionIds);
                })->orWhere(function ($q) use ($episodeIds) {
                    $q->where('quizzable_type', Episode::class)
                        ->whereIn('quizzable_id', $episodeIds);
                });
            })
            ->orderBy('id')
            ->get()
            ->map(fn (Quiz $quiz) => $quiz->toStudentSummary())
            ->values()
            ->all();
    }

}
