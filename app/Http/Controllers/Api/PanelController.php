<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Course;
use App\Models\Path;
use App\Models\Mission;
use App\Models\MissionCategory;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Question;
use App\Models\UserMission;
use App\Models\VideoView;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Rules\ValidGateway;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment as ShetabitPayment;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

class PanelController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();
        $courses = $user->currentCourses()->select('courses.id', 'title', 'english_title', 'slug', 'poster')->orderBy('pivot_created_at', 'desc')->limit(7)->get();
        if ($courses) {
            foreach ($courses as $course) {
                $progress = VideoView::getCourseProgressForUser($user->id, $course->id, 'all');
                $course->totalDuration = $progress['total_duration'];
                $course->watchedDuration = $progress['watched_duration'];
                $course->progressPercentage = $progress['progress_percentage'];
            }
        }
        $questions = $user->questions()->select('id', 'user_id', 'subject', 'slug', 'best_answer', 'created_at', 'updated_at')->with([
            'user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'username', 'profile_pic');
            }
        ])->withCount('answers')->with([
            'latestAnswer' => function ($query) {
                $query->with([
                    'user' => function ($query) {
                        $query->select('id', 'first_name', 'last_name', 'username', 'profile_pic');
                    }
                ]);
            }
        ])->latest()->limit(7)->get();
        $startOfWeek = Carbon::now()->locale('fa')->startOfWeek(Carbon::SATURDAY);
        $endOfWeek = Carbon::now()->locale('fa')->endOfWeek(Carbon::FRIDAY);
        $score = $user->currentScore();
        $scoreOfThisWeek = $user->scores()->whereBetween('created_at', [$startOfWeek, $endOfWeek])->where('score', '>', 0)->sum('score');

        $numberOfCurrentCourse = $user->currentCourses()->count();
        $numberOfCompletedCourse = $user->completedCourses()->count();
        $numberOfQuestion = $user->questions()->count();

        $walletBalance = $user->wallet_balance;

        $questionsOfThisWeek = $user->questions()->whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();
        // second parameter must be in [hour, day, week, month, year, all]
        $watchedTimeThisWeek = VideoView::getWatchedTimeForUserInCurrentPeriod($user->id, 'week');
        $watchedTimeTotal = VideoView::getWatchedTimeForUserInCurrentPeriod($user->id, 'all');

        $activePlan = $user->activeVipPlan();

        // start chart whatched time
        $watchedTimesChart = VideoView::getWatchedChartForUser($user->id, 7);
        // end chart whatched time

        // start chart purchased course
        $purchasedCourseChart = $user->courses()
            ->wherePivot('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(course_user.created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $labels = collect(range(0, 7))->map(fn($i) => now()->subDays(7 - $i)->toDateString());
        $data = $labels->map(fn($date) => $purchasedCourseChart[$date] ?? 0);

        $resultChart = [
            'labels' => $labels,
            'data' => $data,
        ];

        // end chart purchased course

        if ($activePlan) {
            $activePlan = [
                'title' => $activePlan->title,
                'english_title' => $activePlan->english_title,
                'price' => $activePlan->pivot->price,
                'description' => $activePlan->pivot->description,
                'purchase_type' => $activePlan->pivot->purchase_type,
                'expired_at' => $activePlan->pivot->expired_at,
                'created_at' => $activePlan->pivot->created_at,
                'updated_at' => $activePlan->pivot->updated_at,
            ];
        }

        return response()->json([
            'message' => 'success',
            'watched_times_chart' => $watchedTimesChart,
            'purchased_course_chart' => $resultChart,
            'courses' => $courses,
            'questions' => $questions,
            'numberOfCurrentCourse' => $numberOfCurrentCourse,
            'numberOfCompletedCourse' => $numberOfCompletedCourse,
            'numberOfQuestion' => $numberOfQuestion,
            'number_questions_this_week' => $questionsOfThisWeek,
            'score' => $score,
            'score_of_this_week' => $scoreOfThisWeek,
            'walletBalance' => $walletBalance,
            'watchedTimeThisWeek' => $watchedTimeThisWeek,
            'watchedTimeTotal' => $watchedTimeTotal,
            'active_plan' => $activePlan
        ], 200);
    }

    public function financial1(Request $request)
    {
        $filter = $request->input('filter', 'all');
        $type = $request->input('type', 'all');
        $sort = $request->input('sort', 'newest');
        $user = auth('api')->user();

        // Filter based on status and type
        $query = $user->payments()->select('id', 'tracking_number', 'reference_id', 'type', 'amount', 'status', 'created_at');

        // Apply status filter
        $query = match ($filter) {
            'deposit' => $query->where('status', 1),
            'failed' => $query->where('status', 0),
            default => $query,
        };

        // Apply type filter
        $query = match ($type) {
            'course' => $query->where('type', 'course'),
            'wallet' => $query->where('type', 'wallet'),
            'vip' => $query->where('type', 'vip'),
            default => $query,
        };


        $sortOrder = match ($sort) {
            'oldest' => 'asc',
            'newest' => 'desc',
            default => 'desc',
        };

        $query = $query->orderBy('created_at', $sortOrder);
        // Pagination
        $perPage = $request->input('perPage', 10);
        $currentPage = $request->input('page', 1);
        $total = $query->count();
        $lastPage = ceil($total / $perPage);

        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedData = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        $result = $paginatedData->map(function ($payment) {
            return [
                'id' => $payment->id,
                'tracking_number' => $payment->tracking_number,
                'reference_id' => $payment->reference_id,
                'type' => $payment->type,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'created_at' => $payment->created_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'filter' => $filter,
            'type' => $type,
            'data' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => intval($currentPage),
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage
            ]
        ], 200);
    }

    public function financial(Request $request)
    {
        $filter = $request->input('filter', 'all'); // deposit | failed | all
        $type = $request->input('type', 'all');     // course | path | vip | wallet | all
        $sort = $request->input('sort', 'newest');  // newest | oldest
        $user = auth('api')->user();

        $query = $user->payments()
            ->select('id', 'uuid', 'tracking_number', 'reference_id', 'amount', 'driver', 'discount_amount', 'discount_code', 'status', 'paid_at', 'expired_at', 'created_at', 'updated_at')
            ->with(['attempts', 'items.payable']);

        $query = match ($filter) {
            'deposit' => $query->where('status', 1),
            'failed'  => $query->where('status', 0),
            default   => $query,
        };

        if ($type !== 'all') {
            $modelMap = [
                'course' => \App\Models\Course::class,
                'path'   => \App\Models\Path::class,
                'vip'    => \App\Models\Plan::class,
                'wallet' => \App\Models\Wallet::class,
            ];

            if (isset($modelMap[$type])) {
                $query->whereHas('items', function ($q) use ($modelMap, $type) {
                    $q->where('payable_type', $modelMap[$type]);
                });
            }
        }

        // Sorting
        $sortOrder = $sort === 'oldest' ? 'asc' : 'desc';
        $query->orderBy('created_at', $sortOrder);

        // Pagination
        $perPage = $request->input('perPage', 10);
        $currentPage = $request->input('page', 1);
        $total = $query->count();
        $lastPage = ceil($total / $perPage);

        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedData = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Format result
        $result = $paginatedData->map(function ($payment) {
            return [
                'id' => $payment->id,
                'uuid' => $payment->uuid,
                'tracking_number' => $payment->tracking_number,
                'reference_id' => $payment->reference_id,
                'driver' => $payment->driver,
                'amount' => $payment->amount,
                'discount_amount' => $payment->discount_amount,
                'discount_code' => $payment->discount_code,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at,
                'expired_at' => $payment->expired_at,
                'is_paid' => $payment->isPaid(),
                'can_retry' => $payment->canRetry(),
                'created_at' => $payment->created_at,
                'updated_at' => $payment->updated_at,
                'attempts' => $payment->attempts()->latest()->get(),
                'items' => $payment->items->map(function ($item) {
                    $base = [
                        'id' => $item->id,
                        'payable_type' => class_basename($item->payable_type),
                        'payable_id' => $item->payable_id,
                        'price' => $item->price,
                        'discount_amount' => $item->discount_amount,
                        'discount_code' => $item->discount_code,
                        'final_price' => $item->final_price,
                    ];

                    if ($item->relationLoaded('payable') && $item->payable) {
                        $payable = $item->payable;

                        if ($payable instanceof \App\Models\Course) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'slug' => $payable->slug,
                                'poster' => $payable->poster,
                                'price' => $payable->price,
                            ];
                        } elseif ($payable instanceof \App\Models\Path) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'slug' => $payable->slug,
                                'poster' => $payable->poster,
                                'icon' => $payable->icon,
                                'short_description' => $payable->short_description,
                            ];
                        } elseif ($payable instanceof \App\Models\Plan) {
                            $base['payable'] = [
                                'id' => $payable->id,
                                'title' => $payable->title,
                                'english_title' => $payable->english_title,
                                'icon' => $payable->icon,
                                'price' => $payable->price,
                                'period_time' => $payable->period_time,
                                'features' => $payable->features,
                            ];
                        }
                        // elseif ($payable instanceof \App\Models\Wallet) {
                        //     $base['payable'] = [
                        //         'balance' => $payable->balance,
                        //     ];
                        // }
                    }

                    return $base;
                }),
            ];
        });

        return response()->json([
            'message' => 'Success',
            'filter' => $filter,
            'type' => $type,
            'data' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => intval($currentPage),
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage
            ]
        ], 200);
    }

    public function increamentWalletAmount(Request $request)
    {
        $user = auth('api')->user();
        $validData = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:10000', 'max:100000000'],
            'gateway' => ['required', 'string', new ValidGateway],
        ]);

        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        } else {
            $gateway = $request->input('gateway');
            $invoice = new Invoice;
            $invoice->via($gateway);
            $invoice->amount($request->input('amount'));
            do {
                $referenceId = random_int(1000000, 9999999);
            } while (Payment::where('reference_id', $referenceId)->exists());
            $payment = ShetabitPayment::via($gateway)->callbackUrl(route('api.wallet-callback'))->purchase($invoice, function ($driver, $transactionId) use ($invoice, $referenceId) {
                auth('api')->user()->payments()->create([
                    'type' => 'wallet',
                    'payment_info' => null,
                    'driver' => $invoice->getDriver(),
                    'resnumber' => $transactionId,
                    'amount' => $invoice->getAmount(),
                    'reference_id' => $referenceId
                ]);
            })->pay()->toJson();
            return response()->json(['message' => 'Successfully', 'bank_gateway_url' => json_decode($payment)->action], 200);
        }
    }

    public function walletCallback(Request $request)
    {
        try {
            $user = auth('api')->user();
            $authorityParameter = $request->Authority ?? $request->trackId;
            $payment = Payment::where('resnumber', $authorityParameter)->firstOrFail();
            $user = $payment->user;

            $receipt = ShetabitPayment::via($payment->driver)->amount($payment->amount)->transactionId($authorityParameter)->verify();
            $payment->update([
                'status' => 1,
                'tracking_number' => $receipt->getReferenceId(),
            ]);


            do {
                $walletReferenceId = random_int(1000000, 9999999);
            } while (Wallet::where('reference_id', $walletReferenceId)->exists());
            $wallet = $user->wallets()->create([
                'description' => 'افزایش موجودی کیف پول',
                'amount' => $payment->amount,
                'after_balance' => $user->wallet_balance + $payment->amount,
                'type' => 'increase',
                'payment_id' => $payment->id,
                'tracking_number' => $receipt->getReferenceId(),
                'reference_id' => $walletReferenceId
            ]);

            $user->update([
                'wallet_balance' => $wallet->after_balance,
            ]);

            return redirect(env("FRONT_APP_URL") . "/payment/receipt?referenceId={$payment->reference_id}&status=success");
        } catch (InvalidPaymentException $exception) {
            return redirect(env("FRONT_APP_URL") . "/payment/receipt?referenceId={$payment->reference_id}&status=failure");
        }
    }

    public function paymentDetails(Request $request)
    {
        $user = auth('api')->user();
        $reference_id = $request->input('referenceId');
        $payment = Payment::where('reference_id', $reference_id)->firstOrFail();
        if ($user->id == $payment->user_id) {
            $paymentInfo = json_decode($payment->payment_info, true);
            $courses = collect();
            if (isset($paymentInfo['courses'])) {
                foreach ($paymentInfo['courses'] as $courseData) {
                    $courseId = $courseData['course_id'];
                    $purchased_price = $courseData['price'];
                    $course = Course::find($courseId);
                    $course = $course->only('id', 'title', 'english_title', 'slug', 'price', 'poster');
                    $course['purchased_price'] = $purchased_price;
                    $courses->add($course);
                }
            }
            return response()->json(['message' => 'success', 'paymentDetails' => $courses], 200);
        }
        return response()->json(['message' => 'Error: Not found'], 404);
    }

    public function courses(Request $request)
    {
        $filter = $request->input('filter', 'current');
        $user = auth('api')->user();

        $query = match ($filter) {
            'current' => $user->currentCourses(),
            'inactive' => $user->courses()->where('publish', 0),
            'completed' => $user->completedCourses(),
            'purchased' => $user->courses()->wherePivot('price', '>', 0),
            // default => $user->courses(),   // default not re  because filter has default value
        };

        $query = $query->orderBy('created_at', 'desc');

        $perPage = $request->input('perPage', 8);
        $currentPage = $request->input('page', 1);
        $total = $query->count();
        $lastPage = ceil($total / $perPage);

        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedData = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        if ($paginatedData) {
            foreach ($paginatedData as $course) {
                $progress = VideoView::getCourseProgressForUser($user->id, $course->id, 'all');
                $course->totalDuration = $progress['total_duration'];
                $course->watchedDuration = $progress['watched_duration'];
                $course->progressPercentage = $progress['progress_percentage'];
            }
        }

        $result = $paginatedData->map(function ($course) {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'poster' => $course->poster,
                'totalDuration' => $course->totalDuration,
                'watchedDuration' => $course->watchedDuration,
                'progressPercentage' => $course->progressPercentage,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'filter' => $filter,
            'data' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => intval($currentPage),
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage
            ]
        ], 200);
    }

    public function missions(Request $request)
    {
        $categories = MissionCategory::latest()->get();
        $defaultCategory = $request->input('category', $categories->first()->slug);
        $defaultCategory = MissionCategory::where('slug', $defaultCategory)->first();
        $user = auth('api')->user();
        $userId = $user->id;
        $missions = Mission::where('category_id', $defaultCategory->id)->get();
        $userMissions = UserMission::where('user_id', $userId)->get()->keyBy('mission_id');
        $missionsData = [];

        foreach ($missions as $mission) {
            $userMission = $userMissions->get($mission->id);
            $currentLevel = get_mission_current_level($mission, $userMission);
            // $nextLevel = get_mission_next_level($mission, $userMission);
            // $previousLevel = get_mission_previous_level($mission, $userMission);

            $progress = $userMission ? $userMission->progress : 0;
            $progressPercent = calculate_progress_percent($progress, $mission->levels);
            $completed = $userMission && $userMission->completed_at ? true : false;
            $maxLevel = count(json_decode($mission->levels, true));

            $missionData = [
                'id' => (string) $mission->id,
                'title' => $mission->title,
                'description' => $mission->description,
                'icon' => $mission->icon,
                'progress' => $progress,
                'progressPercent' => $progressPercent,
                'currentLevel' => $currentLevel->level,
                'maxLevel' => $maxLevel,
                'completed' => $completed,
                'totalExp' => array_sum(array_column(json_decode($mission->levels, true), 'exp')),
                'levels' => json_decode($mission->levels, true),
            ];

            $missionsData[] = $missionData;
        }

        return response()->json(['message' => 'success', 'missions' => $missionsData, 'categories' => $categories], 200);
    }

    public function questions(Request $request)
    {
        $filter = $request->input('filter', 'current');
        $user = auth('api')->user();

        $query = match ($filter) {
            'current' => Question::where('publish', 1)->where('user_id', $user->id),
            'locked' => Question::where('publish', 0)->where('user_id', $user->id),
            'replies' => Answer::where('user_id', $user->id),
            default => Question::where('publish', 1)->where('user_id', $user->id),
        };

        $query = $query->orderBy('created_at', 'desc');

        $perPage = $request->input('perPage', 6);
        $currentPage = $request->input('page', 1);
        $total = $query->count();
        $lastPage = ceil($total / $perPage);

        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedData = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        $result = $paginatedData->map(function ($item) use ($filter) {
            if ($filter === 'replies') {
                return [
                    'id' => $item->id,
                    'question_id' => $item->question_id,
                    'answer' => $item->answer,
                    'created_at' => $item->created_at,
                    'user' => [
                        'id' => $item->user->id,
                        'first_name' => $item->user->first_name,
                        'last_name' => $item->user->last_name,
                        'username' => $item->user->username,
                        'profile_pic' => $item->user->profile_pic,
                    ],
                    'question' => [
                        'id' => $item->question->id,
                        'subject' => $item->question->subject,
                        'slug' => $item->question->slug,
                    ],
                ];
            } else {
                $lastAnswer = $item->answers()->latest()->first();
                $lastAnswerData = null;

                if ($lastAnswer) {
                    $answerUser = $lastAnswer->user()->select('first_name', 'last_name', 'username', 'profile_pic')->first();
                    $lastAnswerData = [
                        'created_at' => $lastAnswer->created_at,
                        'user' => $answerUser
                    ];
                }
                return [
                    'id' => $item->id,
                    'subject' => $item->subject,
                    'slug' => $item->slug,
                    'question' => $item->question,
                    'user' => [
                        'id' => $item->user->id,
                        'first_name' => $item->user->first_name,
                        'last_name' => $item->user->last_name,
                        'username' => $item->user->username,
                        'profile_pic' => $item->user->profile_pic,
                    ],
                    'answers_count' => $item->answers()->count(),
                    'last_answer' => $lastAnswerData,
                ];
            }
        });

        return response()->json([
            'message' => 'Success',
            'filter' => $filter,
            'data' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => intval($currentPage),
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage
            ]
        ], 200);
    }

    public function certifications(Request $request)
    {
        $filter = $request->input('filter', 'online');
        $user = auth('api')->user();

        $query = match ($filter) {
            'online' => $user->certificates(),
            'tech' => $user->certificates()->where('id', null), // for send null
        };

        $query = $query->orderBy('created_at', 'desc');

        $perPage = $request->input('perPage', 8);
        $currentPage = $request->input('page', 1);
        $total = $query->count();
        $lastPage = ceil($total / $perPage);

        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedData = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        $result = $paginatedData->map(function ($item) use ($filter) {
            if ($filter === 'online') {
                return [
                    'id' => $item->id,
                    'uuid' => $item->uuid,
                    'issued_at' => $item->issued_at,
                    'user_name' => $item->user_name,
                    'course_title' => $item->course_title,
                    'time_completed' => $item->time_completed,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'user' => [
                        'id' => $item->user->id,
                        'first_name' => $item->user->first_name,
                        'last_name' => $item->user->last_name,
                        'username' => $item->user->username,
                        'profile_pic' => $item->user->profile_pic,
                    ],
                    'course' => [
                        'id' => $item->course->id,
                        'title' => $item->course->title,
                        'english_title' => $item->course->english_title,
                        'slug' => $item->course->slug,
                        'poster' => $item->course->poster,
                    ],
                ];
            } else {
            }
        });

        return response()->json([
            'message' => 'Success',
            'filter' => $filter,
            'data' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => intval($currentPage),
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage
            ]
        ], 200);
    }

    public function followed(Request $request)
    {
        $filter = $request->input('filter', 'user');
        $followable_type = match ($filter) {
            'user' => 'App\Models\User',
            'question' => 'App\Models\Question',
            'course' => 'App\Models\Course',
            // 'article' => 'App\Models\Article',
            default => 'App\Models\User',
        };

        $user = auth('api')->user();

        $followed = $user->followings()->where('followable_type', $followable_type)->get();

        $followedPerPage = $request->input('perPage', 6);
        $currentPage = $request->input('page', 1);
        $total = $followed->count();
        $lastPage = ceil($total / $followedPerPage);

        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedFollowed = $followed->slice(($currentPage - 1) * $followedPerPage, $followedPerPage)->values();


        $result = $paginatedFollowed->map(function ($item) use ($followable_type, $user) {
            $followable = $item->followable;

            return match ($followable_type) {
                'App\Models\User' => [
                    'id' => $followable->id,
                    'first_name' => $followable->first_name,
                    'last_name' => $followable->last_name,
                    'username' => $followable->username,
                    'profile_pic' => $followable->profile_pic,
                    'info' => $followable->info,
                    'followings_count' => $followable->followings->count(),
                    'followers_count' => $followable->followers->count(),
                    'hasFlollow' => $user ? $user->isFollowing($followable) : false,
                    'current_score' => $followable->currentScore(),
                ],
                'App\Models\Course' => [
                    'id' => $followable->id,
                    'title' => $followable->title,
                    'english_title' => $followable->english_title,
                    'slug' => $followable->slug,
                    'poster' => $followable->poster,
                    'description' => $followable->description,
                ],
                'App\Models\Question' => [
                    'id' => $followable->id,
                    'subject' => $followable->subject,
                    'slug' => $followable->slug,
                    'question' => $followable->question,
                ],
                default => [],
            };
        });

        return response()->json([
            'message' => 'Success',
            'filter' => $filter,
            'followeds' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => intval($currentPage),
                'per_page' => $followedPerPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage
            ]
        ], 200);
    }

    public function comments(Request $request)
    {
        $filter = $request->input('filter', 'course');
        $commentable_type = match ($filter) {
            // 'article' => 'App\Models\Article',
            'course' => 'App\Models\Course',
            'episode' => 'App\Models\Episode',
            'path' => 'App\Models\Path',
            default => 'App\Models\Course',
        };

        $user = auth('api')->user();

        $sort = $request->input('sort', 'newest');
        $sortOrder = match ($sort) {
            'oldest' => 'asc',
            'newest' => 'desc',
            default => 'desc',
        };

        $status = $request->input('status', 'all');
        $statusFilter = match ($status) {
            'published' => 1,      // Filter for published comments (approved = 1)
            'unpublished' => 0,    // Filter for unpublished comments (approved = 0)
            default => null,       // No specific status filter, show all
        };

        $commentsQuery = $user->comments()->where('commentable_type', $commentable_type);

        if (!is_null($statusFilter)) {
            $commentsQuery->where('approved', $statusFilter);
        }

        $comments = $commentsQuery->orderBy('created_at', $sortOrder)->get();

        $commentsPerPage = $request->input('perPage', 6);
        $currentPage = $request->input('page', 1);
        $total = $comments->count();
        $lastPage = ceil($total / $commentsPerPage);

        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedComments = $comments->slice(($currentPage - 1) * $commentsPerPage, $commentsPerPage)->values();

        $result = $paginatedComments->map(function ($item) use ($commentable_type) {
            $commentable = $item->commentable;

            return [
                'id' => $item->id,
                'comment' => $item->comment,
                'approved' => $item->approved,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'commentable' => match ($commentable_type) {
                    // 'App\Models\Article' => [
                    //     'id' => $commentable->id,
                    //     'title' => $commentable->title,
                    //     'slug' => $commentable->slug,
                    //     'content' => $commentable->content,
                    // ],
                    'App\Models\Course' => [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'poster' => $commentable->poster,
                        'slug' => $commentable->slug,
                        'teacher' => $commentable->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic'),
                    ],
                    'App\Models\Episode' => [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'course' => array_merge(
                            $commentable->section->course->only('id', 'title', 'english_title', 'slug', 'poster'),
                            ['teacher' => $commentable->section->course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')]
                        ),
                        'slug' => $commentable->slug,
                    ],
                    'App\Models\Path' => [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'poster' => $commentable->poster,
                        'icon' => $commentable->icon,
                    ],
                    default => [],
                }
            ];
        });

        return response()->json([
            'message' => 'Success',
            'filter' => $filter,
            'comments' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => intval($currentPage),
                'per_page' => $commentsPerPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage
            ]
        ], 200);
    }
}
