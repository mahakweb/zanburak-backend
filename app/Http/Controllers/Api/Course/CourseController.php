<?php

namespace App\Http\Controllers\Api\Course;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Path;
use App\Models\Status;
use App\Models\VideoView;
use App\Models\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    public function filters()
    {
        $categories = Category::all();
        $statuses = Status::all();
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
        $order = $request->input('order', 'newest');

        $query = Course::where('publish', '1')
            ->cat($cat)
            ->type($type)
            ->order($order);

        $allCourses = $query->get();

        $courses = $allCourses->map(function ($course) use ($user) {
            $teacher = $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic');
            $totalTime = $course->totalTime();
            $likesCount = $course->likes()->count();
            $userHasLiked = $user ? $user->hasLiked($course) : false;

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
                'teacher' => $teacher
            ];
        });

        $coursesPerPage = $request->input('perPage', 12);
        $currentPage = $request->input('page', 1);
        $total = $courses->count();
        $lastPage = ceil($total / $coursesPerPage);
        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedCourses = $courses->slice(($currentPage - 1) * $coursesPerPage, $coursesPerPage)->values();

        $paths = Path::withCount('courses')->get();

        return response()->json([
            'message' => 'Success',
            'paths' => $paths,
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
        // $course->section->each(fn($section) => $section->episode->each(fn($episode) => $episode->number = $episode_number++));

        foreach ($course->section as $section) {
            foreach ($section->episode as $episodeItem) {
                // شماره گذاری اپیزود
                $episodeItem->number = $episode_number++;

                // اگر کاربر لاگین بود
                if ($user) {
                    $progress = VideoView::getEpisodeProgressForUser($user->id, $episodeItem->id);
                    $episodeItem->progressPercentage = $progress['progress_percentage'] ?? 0;

                    $episodeItem->fullWatched = VideoView::hasUserWatchedEpisode($user->id, $episodeItem->id);
                }
            }
        }


        $userCanSeeCourse = $user?->hasCourse($course) ?? false;


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


        $certificate = $user?->certificates()->where('course_id', $course->id)->first();
        $userCompletedCourse = $certificate ? true : false;
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


        return response()->json([
            'message' => 'Success',
            'userCanSeeCourse' => $userCanSeeCourse,
            'userCompletedCourse' => $userCompletedCourse,
            'certificateUuid' => $certificateUuid,
            'relatedCourses' => $relatedCourses,
            'course' => $course,
            'comments_count' => $commentsCount,
            'likes_count' => $likesCount,
            'user_has_liked' => $userHasLiked,
            'bookmarks_count' => $bookmarksCount,
            'user_has_bookmarked' => $userHasBookmarked,
        ], 200);
    }

}
