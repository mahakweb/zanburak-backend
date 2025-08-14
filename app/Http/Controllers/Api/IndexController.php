<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Path;
use App\Models\Plan;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IndexController extends Controller
{
    public function plansList()
    {
        $plans = Plan::select('id', 'title', 'english_title', 'icon', 'status', 'popular', 'price', 'period_time', 'description')->get();
        return response()->json(['message' => 'success', 'plans' => $plans], 200);
    }

    public function latestCourses(Request $request)
    {
        $user = auth('api')->user();
        $rawCourses = Course::latest()->limit(7)->where('publish', '1')->get();
        $courses = $rawCourses->map(function ($course) use ($user) {
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
        return response()->json(['message' => 'success', 'courses' => $courses], 200);
    }

    public function paths(Request $request)
    {
        $paths = Path::where('status', '1')
            ->orderBy('id', 'desc')
            ->select('id', 'title', 'english_title', 'slug', 'short_description', 'poster', 'icon')
            ->get();
        return response()->json(['message' => 'Success', 'paths' => $paths], 200);
    }

    public function getPath(Request $request, $path)
    {
        $user = auth('api')->user();
        $path->load([
            'courses',
            'prerequisites.courses',
            'nextSteps.courses',
        ]);

        $path = [
            'id' => $path->id,
            'title' => $path->title,
            'english_title' => $path->english_title,
            'slug' => $path->slug,
            'icon' => $path->icon,
            'poster' => $path->poster,
            'trailer' => $path->trailer,
            'description' => $path->description,
            'short_description' => $path->short_description,
            'faqs' => json_decode($path->faqs, true),
            'courses' => $path->courses->map(function ($course) use ($user) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'english_title' => $course->english_title,
                    'slug' => $course->slug,
                    'poster' => $course->poster,
                    'likes_count' => $course->likes()->count(),
                    'total_time' => $course->totalTime(),
                    'price' => $course->price,
                    'type' => $course->type,
                    'status' => $course->status->title,
                    'teacher' => $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic'),
                    'user_has_liked' => $user ? $user->hasLiked($course) : false
                ];
            }),

            'prerequisites' => $path->prerequisites->map(function ($prerequisite) use ($user) {
                return [
                    'id' => $prerequisite->id,
                    'title' => $prerequisite->title,
                    'english_title' => $prerequisite->english_title,
                    'slug' => $prerequisite->slug,
                    'icon' => $prerequisite->icon,
                    'poster' => $prerequisite->poster,
                    'trailer' => $prerequisite->trailer,
                    'description' => $prerequisite->description,
                    'short_description' => $prerequisite->short_description,
                    'courses' => $prerequisite->courses->map(function ($course) use ($user) {
                        return [
                            'id' => $course->id,
                            'title' => $course->title,
                            'english_title' => $course->english_title,
                            'slug' => $course->slug,
                            'poster' => $course->poster,
                            'likes_count' => $course->likes()->count(),
                            'total_time' => $course->totalTime(),
                            'price' => $course->price,
                            'type' => $course->type,
                            'status' => $course->status->title,
                            'teacher' => $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic'),
                            'user_has_liked' => $user ? $user->hasLiked($course) : false
                        ];
                    }),
                ];
            }),

            'nextSteps' => $path->nextSteps->map(function ($nextStep) use ($user) {
                return [
                    'id' => $nextStep->id,
                    'title' => $nextStep->title,
                    'english_title' => $nextStep->english_title,
                    'slug' => $nextStep->slug,
                    'icon' => $nextStep->icon,
                    'poster' => $nextStep->poster,
                    'trailer' => $nextStep->trailer,
                    'description' => $nextStep->description,
                    'short_description' => $nextStep->short_description,
                    'courses' => $nextStep->courses->map(function ($course) use ($user) {
                        return [
                            'id' => $course->id,
                            'title' => $course->title,
                            'english_title' => $course->english_title,
                            'slug' => $course->slug,
                            'poster' => $course->poster,
                            'likes_count' => $course->likes()->count(),
                            'total_time' => $course->totalTime(),
                            'price' => $course->price,
                            'type' => $course->type,
                            'status' => $course->status->title,
                            'teacher' => $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic'),
                            'user_has_liked' => $user ? $user->hasLiked($course) : false
                        ];
                    }),
                ];
            }),
        ];

        return response()->json(['message' => 'Success', 'path' => $path], 200);
    }

    public function editorUploadImage(Request $request)
    {

        $validData = Validator::make($request->all(), [
            'image' => ['required', 'mimetypes:image/png,image/jpg,image/jpeg,image/gif', 'max:5120'],
        ], [
            'image.required' => 'ابتدا فایل مورد نظر را انتخاب کنید',
            'image.max' => 'حداکثر سایز تصویر 5 مگابایت میباشد',
            'image.mimetypes' => 'تصویر مورد نظر باید یکی از فرمت‌های: png, jpg, jpeg, gif باشد',
        ]);

        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        } else {
            $storagePath  = Storage::disk('static')->url('');
            $path = Storage::disk('static')->put('/editor/' . now()->year . '/' . now()->month . '/' . now()->day, $request->image);
            return response()->json(['message' => 'Success, data has been successfully saved', 'path' => $storagePath . $path]);
        }
    }


    public function requestProjectCheck(Request $request)
    {
        $user = auth('api')->user();
        return response()->json([
            'email_verified' => !is_null($user->email_verified_at),
            'mobile_verified' => !is_null($user->mobile_verified_at),
        ], 200);
    }

    public function requestProject(Request $request)
    {
        $user = auth('api')->user();

        if (!$user->email_verified_at || !$user->mobile_verified_at) {
            return response()->json([
                'email_verified' => (bool)$user->email_verified_at,
                'mobile_verified' => (bool)$user->mobile_verified_at,
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|min:5|max:255',
            'type' => 'required|in:website,app,websiteAndApp',
            'min_price' => 'required|numeric|min:100000',
            'max_price' => 'required|numeric|min:100000',
            'sample' => 'nullable|url',
            'deadline' => 'required|numeric|min:1',
            'description' => 'required|min:50',
            'attach_file' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();

        $user->project()->create(array_merge($validatedData));

        return response()->json(['message' => 'Success, project has been successfully saved!'], 200);
    }

    public function dropzoneUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpeg,jpg,png,gif,pdf,txt,rar,zip|max:10240', // max 10 MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file format or size',
                'errors' => $validator->errors(),
            ], 400);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = Storage::disk('static')->url(
                Storage::disk('static')->put(
                    '/project/' . now()->format('Y/m/d'),
                    $file
                )
            );
            return response()->json([
                'success' => true,
                'fileUrl' => $filePath,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'No file was uploaded.',
        ], 400);
    }

    public function search(Request $request)
    {
        $user = auth('api')->user();
        $searchKey = $request->input('key');
        $limit = $request->input('limit', 20);

        if (!$searchKey) {
            return response()->json([
                'message' => 'No search key provided',
                'result' => [],
            ], 200);
        }


        $questions = Question::search($searchKey)
            ->where('publish', 1)
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($question) {
                return [
                    'type' => 'question',
                    'id' => $question->id,
                    'subject' => $question->subject,
                    'slug' => $question->slug,
                    'number_of_answers' => $question->answers()->count(),
                ];
            });

        $episodes = Episode::search($searchKey)
            ->where('publish', 1)
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get()
            ->load(['section.course'])
            ->map(function ($episode) {
                $course = optional($episode->section)->course;
                return [
                    'type' => 'episode',
                    'id' => $episode->id,
                    'title' => $episode->title,
                    'english_title' => $episode->english_title,
                    'slug' => $episode->slug,
                    'course' => $course ? [
                        'id' => $course->id,
                        'title' => $course->title,
                        'english_title' => $course->english_title,
                        'slug' => $course->slug,
                        'poster' => $course->poster
                    ] : null,
                ];
            });

        $courses = Course::search($searchKey)
            ->where('publish', 1)
            ->orderBy('id', 'desc')
            ->take($limit)
            ->get()
            ->map(function ($course) {
                return [
                    'type' => 'course',
                    'id' => $course->id,
                    'title' => $course->title,
                    'english_title' => $course->english_title,
                    'slug' => $course->slug,
                    'poster' => $course->poster,
                    'number_of_episodes' => $course->numberOfEpisode(),
                ];
            });

        // Combine all results
        // $result = [
        //     'questions' => $questions,
        //     'episodes' => $episodes,
        //     'courses' => $courses,
        // ];

        $combinedResults = array_merge($courses->toArray(), $episodes->toArray(), $questions->toArray());

        return response()->json([
            'message' => 'Success',
            'result' => $combinedResults,
        ], 200);
    }
}
