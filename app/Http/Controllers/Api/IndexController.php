<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Cooperation;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Path;
use App\Models\Plan;
use App\Models\Question;
use App\Models\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IndexController extends Controller
{
    public $discountPercentForPath = 40;

    public function plansList()
    {
        $plans = Plan::select('id', 'title', 'english_title', 'icon', 'status', 'popular', 'price', 'period_time', 'description', 'features')->where('status', true)->get();
        return response()->json(['message' => 'success', 'plans' => $plans], 200);
    }

    public function categoriesList()
    {
        $categories = Category::withCount('course')->select('id', 'title', 'english_title', 'icon', 'slug', 'status')->where('status', true)->get();
        return response()->json(['message' => 'success', 'categories' => $categories], 200);
    }

    public function latestCourses(Request $request)
    {
        $user = auth('api')->user();
        $limit = $request->input('limit', 10);
        $rawCourses = Course::where('publish', '1');
        if ($limit) {
            $rawCourses = $rawCourses->limit($limit);
        }
        $rawCourses = $rawCourses->orderBy('id', 'desc')->get();

        $courses = $rawCourses->map(function ($course) use ($user) {
            $teacher = $course->teacher
                ? $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                : null;
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

    public function freeCourses(Request $request)
    {
        $user = auth('api')->user();
        $limit = $request->input('limit', 10);
        $rawCourses = Course::where('publish', '1')->where('type', 'free');
        if ($limit) {
            $rawCourses = $rawCourses->limit($limit);
        }
        $rawCourses = $rawCourses->orderBy('id', 'desc')->get();

        $courses = $rawCourses->map(function ($course) use ($user) {
            $teacher = $course->teacher
                ? $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                : null;
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

    // public function paths(Request $request)
    // {
    //     $paths = Path::where('status', '1')
    //         ->orderBy('id', 'desc')
    //         ->select('id', 'title', 'english_title', 'slug', 'short_description', 'poster', 'icon')
    //         ->get();
    //     return response()->json(['message' => 'Success', 'paths' => $paths], 200);
    // }

    public function paths(Request $request)
    {
        $limit = $request->input('limit', 6);
        $showCourses = $request->input('show_courses', false);
        $paths = Path::where('status', '1')
            ->orderBy('id', 'desc')
            ->select('id', 'title', 'english_title', 'slug', 'short_description', 'poster', 'icon');
        if ($limit) {
            $paths = $paths->take($limit);
        }
        if ($showCourses) {
            $paths = $paths->with('publishedCourses:id,title,english_title,slug,short_description,poster,type,price');
        }

        $paths = $paths->get();
        return response()->json(['message' => 'Success', 'paths' => $paths], 200);
    }


    public function getPath(Request $request, $pathSlug)
    {
        $pathModel = $pathSlug instanceof Path ? $pathSlug : Path::where('slug', $pathSlug)->firstOrFail();

        View::createFor($pathModel);

        $user = auth('api')->user();
        $pathModel->load([
            'courses',
            'prerequisites.courses',
            'nextSteps.courses',
            'videos' // ensure relation exists on Path model
        ]);

        $courseIdsInCart = $user ? $user->carts->where('cartable_type', 'App\Models\Course')->pluck('cartable_id')->toArray() : [];


        $userCourseIds = $user ? $user->courses->pluck('id')->toArray() : [];

        $availableCourses = $pathModel->courses->where('publish', true)->filter(function ($course) use ($userCourseIds, $courseIdsInCart) {
            return $course->type !== 'free' && !in_array($course->id, $userCourseIds) && !in_array($course->id, $courseIdsInCart);
        });

        $totalPrice = $availableCourses->sum('price');


        $finalPrice = $totalPrice - ($totalPrice * $this->discountPercentForPath / 100);

        // Build trailer like course: prefer raw video from videos relation
        $rawTrailer = $pathModel->videos ? $pathModel->videos->where('type', 'raw')->first() : null;
        $trailerUrl = null;
        $trailerStatus = null;
        $trailerVideoId = null;
        if ($rawTrailer) {
            // Use disk URL if available, fallback to stored path
            $diskUrl = config("filesystems.disks.{$rawTrailer->disk}.url");
            if (!empty($diskUrl)) {
                $trailerUrl = rtrim($diskUrl, '/') . '/' . ltrim($rawTrailer->path, '/');
            } else {
                $trailerUrl = $rawTrailer->path;
            }
            $trailerStatus = $rawTrailer->status ?? 'uploaded';
            $trailerVideoId = $rawTrailer->id;
        } else {
            // Backward compatibility
            $trailerUrl = $pathModel->trailer;
        }

        $path = [
            'id' => $pathModel->id,
            'title' => $pathModel->title,
            'english_title' => $pathModel->english_title,
            'slug' => $pathModel->slug,
            'icon' => $pathModel->icon,
            'poster' => $pathModel->poster,
            'trailer' => $trailerUrl,
            'trailer_status' => $trailerStatus,
            'trailer_video_id' => $trailerVideoId,
            'description' => $pathModel->description,
            'short_description' => $pathModel->short_description,
            'faqs' => $pathModel->faqs,
            'user_courseIds' => $userCourseIds,
            'user_courseIds_inCart' => $courseIdsInCart,
            'available_courses' => $availableCourses->map->only(['id', 'title', 'english_title', 'short_description', 'profile_pic', 'type', 'poster'])->values(),
            'total_price' => $totalPrice,
            'discount_percent' => $this->discountPercentForPath,
            'final_price' => $finalPrice,
            'courses' => $pathModel->courses->where('publish', true)->map(function ($course) use ($user) {
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
                    'teacher' => $course->teacher
                        ? $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                        : null,
                    'user_has_liked' => $user ? $user->hasLiked($course) : false
                ];
            })->values(),

            'prerequisites' => $pathModel->prerequisites->map(function ($prerequisite) use ($user) {
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
                    'courses' => $prerequisite->courses->where('publish', true)->map(function ($course) use ($user) {
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
                            'teacher' => $course->teacher
                        ? $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                        : null,
                            'user_has_liked' => $user ? $user->hasLiked($course) : false
                        ];
                    })->values(),
                ];
            }),

            'nextSteps' => $pathModel->nextSteps->map(function ($nextStep) use ($user) {
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
                    'courses' => $nextStep->courses->where('publish', true)->map(function ($course) use ($user) {
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
                            'teacher' => $course->teacher
                        ? $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                        : null,
                            'user_has_liked' => $user ? $user->hasLiked($course) : false
                        ];
                    })->values(),
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
            $storagePath = Storage::disk('static')->url('');
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
                'email_verified' => (bool) $user->email_verified_at,
                'mobile_verified' => (bool) $user->mobile_verified_at,
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
        $searchKey = trim((string) $request->input('key', ''));
        $limit = (int) $request->input('limit', 20);

        if ($limit <= 0) {
            $limit = 20;
        }

        if ($searchKey === '') {
            return response()->json([
                'message' => 'No search key provided',
                'result' => [],
            ], 200);
        }

        // جلوگیری از جستجوهای خیلی عمومی مثل "آموزش" یا "دوره" به‌تنهایی
        $genericTerms = ['آموزش', 'اموزش', 'دوره', 'course', 'courses'];
        $tokens = collect(preg_split('/\s+/u', $searchKey, -1, PREG_SPLIT_NO_EMPTY))
            ->filter();

        $genericTermsNormalized = array_map('mb_strtolower', $genericTerms);

        $specificTokens = $tokens->filter(function ($token) use ($genericTermsNormalized) {
            $normalized = mb_strtolower($token);
            return !in_array($normalized, $genericTermsNormalized);
        })->values();

        if ($specificTokens->isEmpty()) {
            return response()->json([
                'message' => 'Query too generic',
                'result' => [],
            ], 200);
        }

        // برای گرفتن نتایج بهتر، کمی بیشتر از limit کلید می‌گیریم
        $scoutTake = $limit * 5;

        // Helper برای چک کردن حضور تمام توکن‌های خاص در متن
        $matchesAllTokens = function (?string $text) use ($specificTokens): bool {
            if (!$text) {
                return false;
            }
            $haystack = mb_strtolower($text);
            foreach ($specificTokens as $token) {
                $t = mb_strtolower($token);
                if ($t !== '' && mb_stripos($haystack, $t) === false) {
                    return false;
                }
            }
            return true;
        };

        // --- Courses ---
        $courseIds = Course::search($searchKey)
            ->take($scoutTake)
            ->keys();

        $courses = collect();
        if ($courseIds->isNotEmpty()) {
            $courses = Course::whereIn('id', $courseIds)
                ->where('publish', 1)
                ->orderByDesc('id')
                ->limit($scoutTake)
                ->get()
                ->filter(function ($course) use ($matchesAllTokens) {
                    $text = trim(implode(' ', array_filter([
                        $course->title,
                        $course->english_title,
                        $course->short_description,
                    ])));
                    return $matchesAllTokens($text);
                })
                ->take($limit)
                ->values()
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
        }

        // --- Episodes ---
        $episodeIds = Episode::search($searchKey)
            ->take($scoutTake)
            ->keys();

        $episodes = collect();
        if ($episodeIds->isNotEmpty()) {
            $episodes = Episode::whereIn('id', $episodeIds)
                ->where('publish', 1)
                ->orderByDesc('id')
                ->with(['section.course'])
                ->limit($scoutTake)
                ->get()
                ->filter(function ($episode) use ($matchesAllTokens) {
                    $text = trim(implode(' ', array_filter([
                        $episode->title,
                        $episode->english_title,
                        $episode->description,
                    ])));
                    return $matchesAllTokens($text);
                })
                ->take($limit)
                ->values()
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
                            'poster' => $course->poster,
                        ] : null,
                    ];
                });
        }

        // --- Questions ---
        $questionIds = Question::search($searchKey)
            ->take($scoutTake)
            ->keys();

        $questions = collect();
        if ($questionIds->isNotEmpty()) {
            $questions = Question::whereIn('id', $questionIds)
                ->where('publish', 1)
                ->orderByDesc('id')
                ->limit($scoutTake)
                ->get()
                ->filter(function ($question) use ($matchesAllTokens) {
                    $text = trim(implode(' ', array_filter([
                        $question->subject,
                        $question->question,
                    ])));
                    return $matchesAllTokens($text);
                })
                ->take($limit)
                ->values()
                ->map(function ($question) {
                    return [
                        'type' => 'question',
                        'id' => $question->id,
                        'subject' => $question->subject,
                        'slug' => $question->slug,
                        'number_of_answers' => $question->answers()->count(),
                    ];
                });
        }

        $combinedResults = array_merge(
            $courses->toArray(),
            $episodes->toArray(),
            $questions->toArray()
        );

        return response()->json([
            'message' => 'Success',
            'result' => $combinedResults,
        ], 200);
    }

    public function cooperation(Request $request)
    {
        // $user = auth('api')->user();

        // Optional: enforce verification like project request

        // if (!$user->email_verified_at || !$user->mobile_verified_at) {
        //     return response()->json([
        //         'email_verified' => (bool) $user->email_verified_at,
        //         'mobile_verified' => (bool) $user->mobile_verified_at,
        //     ], 401);
        // }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:support,teacher,content_creator,dev,marketing,design',
            'name' => 'required|string|min:3|max:25',
            'mobile' => ['required', 'regex:/^(\+98|0)?9\d{9}$/'],
            'email' => 'nullable|email|max:255',
            'melli_code' => 'required|digits:10',
            // 'iban' => ['required', 'string', 'max:34'],
            'description' => 'required|min:20|max:2000',
            'links' => 'nullable|string|max:1000',
            'melli_card_image' => 'required|string|max:255',
            'resume' => 'nullable|string|max:255',
            'samples' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error', 'errors' => $validator->errors()], 422);
        }

        $validData = $validator->validated();

        $existing = Cooperation::where('melli_code', $validData['melli_code'])
            ->where('role', $validData['role'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'برای این کد ملی و این عنوان شغلی قبلا درخواست ثبت شده است.'], 422);
        }

        Cooperation::create($validator->validated());

        // Persist as a JSON file to storage (no DB migration needed)
        // $payload = $validator->validated();
        // $payload['user_id'] = $user->id;
        // $payload['created_at'] = now()->toDateTimeString();
        // $fileName = 'cooperation/' . now()->format('Y/m/d') . '/' . $user->id . '_' . now()->timestamp . '.json';
        // Storage::disk('local')->put($fileName, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return response()->json(['message' => 'Success, cooperation request has been received!'], 200);
    }

    public function validateIban(Request $request)
    {
        $request->validate([
            'iban' => 'required|string|max:34'
        ]);

        $iban = strtoupper(str_replace(' ', '', $request->input('iban')));
        if (!str_starts_with($iban, 'IR')) {
            $iban = 'IR' . preg_replace('/^IR/i', '', $iban);
        }

        // Basic format check: IR + 24 digits
        if (!preg_match('/^IR[0-9]{24}$/', $iban)) {
            return response()->json(['valid' => false, 'message' => 'Invalid IBAN format'], 200);
        }

        // Checksum (mod 97) validation
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $converted = preg_replace_callback('/[A-Z]/', function ($m) {
            return ord($m[0]) - 55;
        }, $rearranged);

        // Calculate mod 97 without BCMath (iterate digit-by-digit)
        $remainder = 0;
        $len = strlen($converted);
        for ($i = 0; $i < $len; $i++) {
            $digit = intval($converted[$i]);
            $remainder = ($remainder * 10 + $digit) % 97;
        }
        if ($remainder !== 1) {
            return response()->json(['valid' => false, 'message' => 'Invalid IBAN checksum'], 200);
        }

        // Lightweight bank detection (best-effort)
        // In Iranian IBAN, after IRkk the next few digits begin the BBAN containing bank identifier.
        // Per https://almico.ir/blog/sheba bank code is digits 4-5 of IBAN (excluding IR)
        $digits = substr($iban, 2); // 24 digits
        $bankCode2 = substr($digits, 3, 2);
        $bankMap2 = [
            '11' => 'بانک صنعت و معدن',
            '12' => 'بانک ملت',
            '13' => 'بانک رفاه کارگران',
            '14' => 'بانک مسکن',
            '15' => 'بانک سپه',
            '16' => 'بانک کشاورزی',
            '17' => 'بانک ملی ایران',
            '18' => 'بانک تجارت',
            '19' => 'بانک صادرات ایران',
            '20' => 'بانک توسعه صادرات',
            '21' => 'پست بانک',
            '22' => 'بانک توسعه تعاون',
            '52' => 'بانک قوامین',
            '53' => 'بانک کارآفرین',
            '54' => 'بانک پارسیان',
            '55' => 'بانک اقتصاد نوین',
            '56' => 'بانک سامان',
            '57' => 'بانک پاسارگاد',
            '58' => 'بانک سرمایه',
            '59' => 'بانک سینا',
            '60' => 'بانک قرض الحسنه مهر ایران',
            '61' => 'بانک شهر',
            '62' => 'بانک آینده',
            '63' => 'بانک انصار',
            '64' => 'بانک گردشگری',
            '65' => 'بانک حکمت ایرانیان',
            '66' => 'بانک دی',
            '69' => 'بانک ایران زمین',
            '70' => 'بانک قرض الحسنه رسالت',
            '75' => 'مؤسسه اعتباری ملل',
            '79' => 'بانک مهر اقتصاد',
            '80' => 'بانک خاورمیانه',
        ];
        $bankName = $bankMap2[$bankCode2] ?? null;

        return response()->json([
            'valid' => true,
            'bank_name' => $bankName,
            'bank_code' => $bankCode2,
        ], 200);
    }



    public function uploadFileCooperation(Request $request)
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
                    '/cooperation/' . now()->format('Y/m/d'),
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
}
