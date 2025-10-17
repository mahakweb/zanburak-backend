<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Level;
use App\Models\Status;
use App\Models\User;
use App\Models\VideoView;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\UploadTokenService;

class CourseController extends Controller
{
    public function courses(Request $request)
    {
        $query = Course::with([
            'category:id,title,slug,english_title',
            'level:id,title,slug,english_title',
            'status:id,title,slug,english_title',
        ])
            ->publish($request->input('publish'))
            ->cat($request->input('category'))
            ->type($request->input('type'))
            ->level($request->input('level'))
            ->status($request->input('status'))
            ->order($request->input('sort', 'newest'));

        $perPage = $request->input('perPage', 10);
        $courses = $query->paginate($perPage);

        $data = $courses->map(function ($course) {
            return [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'poster' => $course->poster,
                'publish' => $course->publish,
                'type' => $course->type,
                'description' => $course->description,
                'short_description' => $course->short_description,
                'total_time' => $course->totalTime(false),
                'teacher' => $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic'),
                'created_at' => $course->created_at,
                'updated_at' => $course->updated_at,
                'section_count' => $course->numberOfSection(),
                'episode_count' => $course->numberOfAllEpisode(),

                'categories' => $course->category->map(fn($cat) => [
                    'id' => $cat->id,
                    'title' => $cat->title,
                    'slug' => $cat->slug,
                    'english_title' => $cat->english_title,
                ]),

                'level' => $course->level ? [
                    'id' => $course->level->id,
                    'title' => $course->level->title,
                    'slug' => $course->level->slug,
                    'english_title' => $course->level->english_title,
                ] : null,

                'status' => $course->status ? [
                    'id' => $course->status->id,
                    'title' => $course->status->title,
                    'slug' => $course->status->slug,
                    'english_title' => $course->status->english_title,
                ] : null,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'courses' => $data,
            'pagination' => [
                'total' => $courses->total(),
                'per_page' => $courses->perPage(),
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'prev_page' => $courses->currentPage() > 1 ? $courses->currentPage() - 1 : null,
                'next_page' => $courses->hasMorePages() ? $courses->currentPage() + 1 : null
            ]
        ]);
    }

    public function baseDetails(Request $request, $course)
    {
        $categories = $course->category->map(fn($cat) => [
            'id' => $cat->id,
            'title' => $cat->title,
            'slug' => $cat->slug,
            'english_title' => $cat->english_title,
        ]);

        $level = $course->level ? [
            'id' => $course->level->id,
            'title' => $course->level->title,
            'slug' => $course->level->slug,
            'english_title' => $course->level->english_title,
        ] : null;

        $status = $course->status ? [
            'id' => $course->status->id,
            'title' => $course->status->title,
            'slug' => $course->status->slug,
            'english_title' => $course->status->english_title,
        ] : null;

        $response = [
            'title' => $course->title,
            'english_title' => $course->english_title,
            'slug' => $course->slug,
            'poster' => $course->poster,
            // 'short_description' => $course->short_description,
            // 'description' => $course->description,
            // 'start_date' => $course->start_date,
            // 'end_date' => $course->end_date,
            'type' => $course->type,
            'publish' => $course->publish,
            'price' => $course->price,
            'level' => $level,
            'status' => $status,
            'categories' => $categories,
            'tags' => $course->tags->pluck('name'),
            'averageRating' => $course->averageRating(),
            'totalTime' => $course->totalTime(false),
            'usersCount' => $course->users()->count(),
            'viewsCount' => $course->views()->count(),
            'likesCount' => $course->likes()->count(),
            'commentsCount' => $course->comments()->count(),
            'sectionsCount' => $course->numberOfSection(),
            'episodesCount' => $course->numberOfAllEpisode(),
            'created_at' => $course->created_at,
            'updated_at' => $course->updated_at,
        ];
        return response()->json(['message' => 'Success', 'course' => $response], 200);
    }

    public function overview(Request $request, $course)
    {
        $teacher = $course->teacher ? [
            'id' => $course->teacher->id,
            'first_name' => $course->teacher->first_name,
            'last_name' => $course->teacher->last_name,
            'profile_pic' => $course->teacher->profile_pic,
            'cover_pic' => $course->teacher->cover_pic,
            'username' => $course->teacher->username,
        ] : null;

        $rowAttach = $course->attachs->first();
        $attach = null;
        if ($rowAttach) {
            $attach = [
                'url' => $rowAttach->url,
                'size' => $this->urlDetails($rowAttach->url)['size']
            ];
        }


        $rawTrailer = $course->videos
            ->where('type', 'raw')
            ->first();

        $trailerUrl = null;
        $trailerStatus = null;
        $trailerVideoId = null;
        if ($rawTrailer) {
            $diskUrl = config("filesystems.disks.{$rawTrailer->disk}.url");
            if ($diskUrl) {
                $trailerUrl = rtrim($diskUrl, '/') . '/' . ltrim($rawTrailer->path, '/');
            } else {
                $trailerUrl = $rawTrailer->path;
            }
            $trailerStatus = $rawTrailer->status ?? 'queued';
            $trailerVideoId = $rawTrailer->id;
        }



        $recentComments = $course->comments()
            ->latest()
            ->take(5)
            ->with('user:id,first_name,last_name,username,profile_pic')
            ->get()
            ->map(function ($comment) {
                return [
                    'user' => $comment->user ? [
                        'id' => $comment->user->id,
                        'first_name' => $comment->user->first_name,
                        'last_name' => $comment->user->last_name,
                        'username' => $comment->user->username,
                        'profile_pic' => $comment->user->profile_pic,
                    ] : null,
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'approved' => $comment->approved,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ];
            });

        // $recentComments = $course->comments()
        //     ->join('users', 'comments.user_id', '=', 'users.id')
        //     ->where('users.is_superuser', false)
        //     ->where('users.is_staff', false)
        //     ->select([
        //         'comments.id',
        //         'comments.comment',
        //         'comments.approved',
        //         'comments.created_at',
        //         'comments.updated_at',
        //         'comments.parent_id',
        //         'users.id as user_id',
        //         'users.first_name',
        //         'users.last_name',
        //         'users.username',
        //         'users.profile_pic'
        //     ])
        //     ->latest('comments.created_at')
        //     ->take(5)
        //     ->get()
        //     ->map(function ($comment) {
        //         return [
        //             'id' => $comment->id,
        //             'comment' => $comment->comment,
        //             'approved' => $comment->approved,
        //             'created_at' => $comment->created_at,
        //             'updated_at' => $comment->updated_at,
        //             'parent_id' => $comment->parent_id,
        //             'user' => [
        //                 'id' => $comment->user_id,
        //                 'first_name' => $comment->first_name,
        //                 'last_name' => $comment->last_name,
        //                 'username' => $comment->username,
        //                 'profile_pic' => $comment->profile_pic,
        //             ],
        //         ];
        //     });



        $recentRegistrations = $course->users()
            ->latest('pivot_created_at')
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'profile_pic' => $user->profile_pic,
                    'registered_at' => $user->pivot->created_at,
                ];
            });

        $registrationsChart = $course->users()
            ->wherePivot('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(course_user.created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');


        $viewsChart = $course->views()
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');


        $charts = [
            'registrations' => [
                'labels' => $registrationsChart->keys(),
                'data' => $registrationsChart->values(),
            ],
            'views' => [
                'labels' => $viewsChart->keys(),
                'data' => $viewsChart->values(),
            ],
        ];


        // $start = now()->subDays(30)->startOfDay(); // بازه ۳۰ روز گذشته
        // $end = now()->endOfDay();
        // $period = CarbonPeriod::create($start, $end);
        // $registrations = $course->users()
        //     ->selectRaw('DATE(course_user.created_at) as date, COUNT(*) as count')
        //     ->wherePivot('created_at', '>=', $start)
        //     ->groupBy('date')
        //     ->orderBy('date', 'asc')
        //     ->pluck('count', 'date'); // key = date, value = count

        // $registrationsChart = [
        //     'labels' => [],
        //     'data' => [],
        // ];

        // foreach ($period as $date) {
        //     $formattedDate = $date->toDateString();
        //     $registrationsChart['labels'][] = $formattedDate;
        //     $registrationsChart['data'][] = $registrations[$formattedDate] ?? 0;
        // }

        // $views = $course->views()
        //     ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
        //     ->where('created_at', '>=', $start)
        //     ->groupBy('date')
        //     ->orderBy('date', 'asc')
        //     ->pluck('count', 'date');

        // $viewsChart = [
        //     'labels' => [],
        //     'data' => [],
        // ];

        // foreach ($period as $date) {
        //     $formattedDate = $date->toDateString();
        //     $viewsChart['labels'][] = $formattedDate;
        //     $viewsChart['data'][] = $views[$formattedDate] ?? 0;
        // }


        // $charts = [
        //     'registrations' => $registrationsChart,
        //     'views' => $viewsChart,
        // ];


        $response = [
            'title' => $course->title,
            'english_title' => $course->english_title,
            'slug' => $course->slug,
            'short_description' => $course->short_description,
            'description' => $course->description,
            'start_date' => $course->start_date,
            'end_date' => $course->end_date,
            'teacher' => $teacher,
            'attach' => $attach,
            'trailer' => $trailerUrl,
            'trailer_status' => $trailerStatus,
            'trailer_video_id' => $trailerVideoId,
            'recent_activities' => [
                'comments' => $recentComments,
                'registrations' => $recentRegistrations,
            ],
            'charts' => $charts,
            'certificatesCount' => $course->certificates()->count(),
            'totalSales' => $course->users()->sum('course_user.price'),
            'created_at' => $course->created_at,
            'updated_at' => $course->updated_at,
        ];

        $extra = [];
        if ((bool) $request->input('get_worker_credentials')) {
            $claims = [
                'type' => 'video',
                'courseId' => $course->id,
                'userId' => optional(auth('api')->user())->id,
            ];
            $tokenData = UploadTokenService::generate($claims);
            $extra['worker_token'] = $tokenData['token'];
            $extra['worker_origin'] = rtrim(config('upload.worker_base_url'), '/');
        }

        return response()->json(array_merge(['message' => 'Success', 'course' => $response], $extra), 200);
    }

    public function comments(Request $request, $course)
    {
        $perPage = (int) $request->input('perPage', 10);
        $page = (int) $request->input('page', 1);
        $approved = $request->input('approved', null);
        $order = strtolower($request->input('order', 'desc'));

        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'desc';
        }

        $query = $course->comments()->where('parent_id', 0);

        if (!is_null($approved)) {
            $query->where('approved', filter_var($approved, FILTER_VALIDATE_BOOLEAN));
        }

        $totalComments = $query->count();
        $lastPage = (int) ceil($totalComments / $perPage);

        if ($page > $lastPage && $lastPage > 0) {
            $page = 1;
        }

        $paginator = $query
            ->with(['user', 'childs.user'])
            ->orderBy('id', $order)
            ->paginate($perPage, ['*'], 'page', $page);

        $data = $paginator->getCollection()->transform(function ($parent) use ($approved, $order) {
            $descendants = $parent->descendants($approved)
                ->filter(function ($descendant) use ($approved) {
                    if (is_null($approved))
                        return true;
                    return $descendant->approved == $approved;
                })
                ->sortBy([
                    ['created_at', $order] 
                ])
                ->values()
                ->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'comment' => $child->comment,
                        'approved' => $child->approved,
                        'created_at' => $child->created_at,
                        'user' => [
                            'id' => $child->user->id,
                            'first_name' => $child->user->first_name,
                            'last_name' => $child->user->last_name,
                            'username' => $child->user->username,
                            'profile_pic' => $child->user->profile_pic,
                        ]
                    ];
                });

            return [
                'id' => $parent->id,
                'comment' => $parent->comment,
                'approved' => $parent->approved,
                'created_at' => $parent->created_at,
                'user' => [
                    'id' => $parent->user->id,
                    'first_name' => $parent->user->first_name,
                    'last_name' => $parent->user->last_name,
                    'username' => $parent->user->username,
                    'profile_pic' => $parent->user->profile_pic,
                ],
                'children' => $descendants
            ];
        });

        $paginator->setCollection($data);

        return response()->json([
            'message' => 'Success',
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'poster' => $course->poster,
                'type' => $course->type,
            ],
            'comments' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'prev_page' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null
            ]
        ]);
    }



    public function users(Request $request, Course $course)
    {
        $perPage = (int) $request->input('perPage', 10);
        $page = (int) $request->input('page', 1);

        $totalUsers = $course->users()->count();

        $lastPage = (int) ceil($totalUsers / $perPage);

        if ($page > $lastPage && $lastPage > 0) {
            $page = 1;
        }

        $paginator = $course->users()
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.username', 'users.profile_pic', 'users.cover_pic')
            ->orderByPivot('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $data = $paginator->getCollection()->transform(function ($user) use ($course) {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'username' => $user->username,
                'profile_pic' => $user->profile_pic,
                'cover_pic' => $user->cover_pic,

                'price' => $user->pivot->price,
                // 'payment_id' => $user->pivot->payment_id,
                'purchased_at' => $user->pivot->created_at,
                'completed_at' => $user->pivot->completed_at,

                'watched_percent' => VideoView::getCourseProgressForUser($user->id, $course->id)['progress_percentage'],
            ];
        });

        $paginator->setCollection($data);

        return response()->json([
            'message' => 'Success',
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'poster' => $course->poster,
                'type' => $course->type,
            ],
            'users' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'prev_page' => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
                'next_page' => $paginator->hasMorePages() ? $paginator->currentPage() + 1 : null
            ]
        ]);
    }

    public function assignToUser(Request $request, $course)
    {
        $user = User::find($request->input('user_id'));
        if (!$user) {
            return response()->json(['message' => 'Error!, user not found.'], 404);
        }

        if ($user->courses()->where('courses.id', $course->id)->exists()) {
            return response()->json([
                'message' => 'Error! this course already assigned to this user.'
            ], 409);
        }

        $user->courses()->attach([
            [
                'course_id' => $course->id,
                'payment_id' => null,
                'price' => 0
            ]
        ]);

        $result = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'profile_pic' => $user->profile_pic,
            'cover_pic' => $user->cover_pic,

            'price' => 0,
            // 'payment_id' => null,
            'purchased_at' => now(),
            'completed_at' => null,

            'watched_percent' => 0,
        ];

        return response()->json(['message' => 'Success', 'result' => $result], 200);
    }
    public function episodes(Request $request, $course)
    {
        $sections = $course->section?->load('episode') ?? collect([]);

        $sections = $sections->map(function ($section) {
            return [
                'id' => $section->id,
                'title' => $section->title,
                'english_title' => $section->english_title,
                'slug' => $section->slug,
                'attached_file' => $section->attached_file,
                'course_id' => $section->course_id,
                'description' => $section->description,
                'publish' => $section->publish,
                'start_date' => $section->start_date,
                'end_date' => $section->end_date,
                'status' => $section->status,
                'episodes' => $section->episode->sortBy('order')->values()->all(),
                'created_at' => $section->created_at,
                'updated_at' => $section->updated_at,
            ];
        });

        $response = [
            'id' => $course->id,
            'title' => $course->title,
            'english_title' => $course->english_title,
            'slug' => $course->slug,
            'poster' => $course->poster,
            'sections' => $sections,
        ];

        return response()->json([
            'message' => 'Success',
            'course' => $response,
        ], 200);
    }

    public function reorderEpisodes(Request $request, Course $course)
    {
        $request->validate([
            'episodes' => 'required|array',
            'episodes.*.id' => 'required|integer|exists:episodes,id',
            'episodes.*.section_id' => 'required|integer|exists:sections,id',
            'episodes.*.order' => 'required|integer|min:1',
        ]);

        foreach ($request->episodes as $episode) {
            Episode::where('id', $episode['id'])->update([
                'section_id' => $episode['section_id'],
                'order' => $episode['order'],
            ]);
        }

        return response()->json(['message' => 'Episodes reordered successfully.']);
    }

    public function getInitData(Request $request)
    {
        $categories = Category::select('id', 'title', 'english_title', 'slug')->where('status', '1')->get();
        $statuses = Status::select('id', 'title', 'english_title', 'slug')->get();
        $levels = Level::select('id', 'title', 'english_title', 'slug')->get();
        return response()->json(['message' => 'Success', 'categories' => $categories, 'statuses' => $statuses, 'levels' => $levels], 200);
    }
    public function store(Request $request)
    {
        // return $request->all();
        $validator = Validator::make($request->all(), [
            'status_id' => ['required', 'exists:statuses,id'],
            'level_id' => ['required', 'exists:levels,id'],
            'type' => ['required', 'in:free,cash,cash-vip'],
            'categories' => ['required', 'array'],
            'paths' => ['nullable', 'array'],
            'paths.*' => ['exists:paths,id'],
            'tags' => ['nullable', 'array'],
            'title' => ['required', 'min:10', 'max:255', 'unique:courses,title'],
            'english_title' => ['required', 'min:10', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', 'unique:courses,english_title'],
            'short_description' => ['required', 'min:10'],
            'description' => ['required', 'min:10'],
            'publish' => ['required', 'boolean'],
            'price' => ['required', 'numeric', 'max:100000000'],
            'start_date' => ['nullable', "date", "before:end_date"],
            'end_date' => ['nullable', "date", "after:start_date"],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();

            $course = $user->addCourse()->create($validData);
            $course->category()->attach($request['categories']);

            // Attach paths if provided
            if (!empty($request['paths'])) {
                $course->paths()->attach($request['paths']);
            }

            $tags = is_string($request->tags) ? json_decode($request->tags, true) : $request->tags;

            foreach ($tags as $tag) {
                $course->tag($tag);
            }

            return response()->json(['message' => "Course created successfully", 'course' => $course], 200);

        }
    }

    public function edit(Request $request)
    {
        $slug = $request->slug;
        $course = Course::with(['category', 'tags', 'level', 'status', 'videos', 'paths'])->where('slug', $slug)->first();

        if (!$course) {
            return response()->json(['message' => 'Error! course not found'], 404);
        }

        $categories = $course->category->map(fn($cat) => [
            'id' => $cat->id,
            'title' => $cat->title,
            'slug' => $cat->slug,
            'english_title' => $cat->english_title,
        ]);

        $level = $course->level ? [
            'id' => $course->level->id,
            'title' => $course->level->title,
            'slug' => $course->level->slug,
            'english_title' => $course->level->english_title,
        ] : null;

        $status = $course->status ? [
            'id' => $course->status->id,
            'title' => $course->status->title,
            'slug' => $course->status->slug,
            'english_title' => $course->status->english_title,
        ] : null;

        // $tags = $course->tags->map(fn($tag) => [
        //     'name' => $tag->name,
        //     'normalized' => $tag->normalized,
        // ]);

        $tags = $course->tags->pluck('name');

        $paths = $course->paths->map(fn($path) => [
            'id' => $path->id,
            'title' => $path->title,
            'english_title' => $path->english_title,
            'slug' => $path->slug,
        ]);

        $rawTrailer = $course->videos
            ->where('type', 'raw')
            ->first();

        $trailerUrl = null;
        $trailerStatus = null;
        $trailerVideoId = null;
        if ($rawTrailer) {
            $trailerUrl = Storage::disk($rawTrailer->disk)->url($rawTrailer->path);
            $trailerStatus = $rawTrailer->status ?? 'queued';
            $trailerVideoId = $rawTrailer->id;
        }

        $rowAttach = $course->attachs->first();
        $attach = null;
        if ($rowAttach) {
            $attach = [
                'url' => $rowAttach->url,
                'size' => $this->urlDetails($rowAttach->url)['size']
            ];
        }


        $response = [
            'id' => $course->id,
            'title' => $course->title,
            'english_title' => $course->english_title,
            'slug' => $course->slug,
            'type' => $course->type,
            'short_description' => $course->short_description,
            'description' => $course->description,
            'poster' => $course->poster,
            'publish' => $course->publish,
            'price' => $course->price,
            'start_date' => $course->start_date,
            'end_date' => $course->end_date,
            'categories' => $categories,
            'paths' => $paths,
            'tags' => $tags,
            'level' => $level,
            'status' => $status,
            'trailer' => $trailerUrl,
            'trailer_status' => $trailerStatus,
            'trailer_video_id' => $trailerVideoId,
            'attach' => $attach,
        ];

        return response()->json([
            'message' => 'Success',
            'course' => $response,
        ]);
    }

    public function update(Request $request)
    {
        $user = auth('api')->user();
        $course = Course::find($request->input('course_id'));
        if (!$course) {
            return response()->json(['message' => 'Error! Course not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status_id' => ['required', 'exists:statuses,id'],
            'level_id' => ['required', 'exists:levels,id'],
            'type' => ['required', 'in:free,cash,cash-vip'],
            'categories' => ['required', 'array'],
            'paths' => ['nullable', 'array'],
            'paths.*' => ['exists:paths,id'],
            'tags' => ['nullable', 'array'],
            'title' => ['required', 'min:10', 'max:255', Rule::unique('courses', 'title')->ignore($course->id)],
            'english_title' => ['required', 'min:10', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', Rule::unique('courses', 'english_title')->ignore($course->id)],
            'short_description' => ['required', 'min:10'],
            'description' => ['required', 'min:10'],
            'publish' => ['required', 'boolean'],
            'price' => ['required', 'numeric', 'max:100000000'],
            'start_date' => ['nullable', "date", "before:end_date"],
            'end_date' => ['nullable', "date", "after:start_date"],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            $course->update($validData);
            $course->category()->sync($validData['categories']);
            
            // Sync paths if provided
            if (isset($validData['paths'])) {
                $course->paths()->sync($validData['paths']);
            }
            
            $course->retag($validData['tags']);

            return response()->json(['message' => "Course updated successfully", 'course' => $course], 200);

        }
    }


    public function uploadPoster(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => ['required', 'exists:courses,id'],
            'filename' => ['required', 'string'],
            'mime' => ['required', 'string'],
            'size' => ['required', 'integer', 'min:1'],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();
            $course = Course::findOrFail($request->course_id);
            if (!$course) {
                return response()->json(['message' => 'Error! course not found'], 404);
            }
            $disk = 'static';
            $folder = "poster/" . date('Y/m/d');
            $ext = pathinfo($validData['filename'], PATHINFO_EXTENSION);
            $generated = Str::uuid()->toString();
            $filePath = "{$folder}/{$generated}.{$ext}";

            $claims = [
                'sub' => 'upload',
                'type' => 'poster',
                'disk' => $disk,
                'path' => $filePath,
                'mime' => $validData['mime'],
                'size' => (int) $validData['size'],
                'courseId' => $course->id,
                'userId' => optional($user)->id,
            ];

            $tokenData = UploadTokenService::generate($claims);

            return response()->json([
                'message' => 'Upload initialized. Use worker to upload the file.',
                'uploadPath' => $filePath,
                'uploadToken' => $tokenData['token'],
                'workerUploadUrl' => rtrim(config('upload.worker_base_url'), '/') . '/api/upload/attachment',
                'expiresAt' => $tokenData['expires_at'],
            ], 200);

        }
    }

    public function removePoster($course)
    {
        if ($course->poster) {
            $disk = $this->urlDetails($course->poster)['disk'];
            $path = $this->urlDetails($course->poster)['path'];
            if ($course->poster && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                $course->poster = null;
                $course->save();
            }
        }
    }

    public function uploadAttachedFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => ['required', 'exists:courses,id'],
            'filename' => ['required', 'string'],
            'mime' => ['required', 'string'],
            'size' => ['required', 'integer', 'min:1'],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();
            $course = Course::findOrFail($request->course_id);
            if (!$course) {
                return response()->json(['message' => 'Error! course not found'], 404);
            }


            $disk = 'static';
            $folder = "attach/" . date('Y/m/d');
            $ext = pathinfo($validData['filename'], PATHINFO_EXTENSION);
            $generated = Str::uuid()->toString();
            $filePath = "{$folder}/{$generated}.{$ext}";

            $claims = [
                'sub' => 'upload',
                'type' => 'attachment',
                'disk' => $disk,
                'path' => $filePath,
                'mime' => $validData['mime'],
                'size' => (int) $validData['size'],
                'courseId' => $course->id,
                'userId' => optional($user)->id,
            ];

            $tokenData = UploadTokenService::generate($claims);

            return response()->json([
                'message' => 'Upload initialized. Use worker to upload the file.',
                'uploadPath' => $filePath,
                'uploadToken' => $tokenData['token'],
                'workerUploadUrl' => rtrim(config('upload.worker_base_url'), '/') . '/api/upload/attachment',
                'expiresAt' => $tokenData['expires_at'],
            ], 200);

        }
    }

    public function removeAttachedFile($course)
    {
        $attach = $course->attachs->first();
        if ($attach) {
            $disk = $this->urlDetails($attach->url)['disk'];
            $path = $this->urlDetails($attach->url)['path'];
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                $course->attachs()->delete();
            }
        }
    }

    public function removeTrailer($course)
    {
        $videos = $course->videos;
        if ($videos) {
            foreach ($videos as $vid) {
                $folderPath = dirname($vid->path);
                $storage = Storage::disk($vid->disk);
                if ($vid->type == 'trailer' && Storage::disk($vid->disk)->exists($vid->path)) {
                    if ($storage->exists($folderPath)) {
                        $files = $storage->allFiles($folderPath);
                        foreach ($files as $file) {
                            $storage->delete($file);
                        }
                        $storage->deleteDirectory($folderPath);
                    }
                } else if (Storage::disk($vid->disk)->exists($vid->path)) {
                    Storage::disk($vid->disk)->delete($vid->path);
                    $storage->deleteDirectory($folderPath);
                }
            }
            $course->videos()->delete();
            
            // Note: Course total_time is calculated automatically from episodes
            // No need to update course total_time as it's computed dynamically
        }
    }

    public function deleteCourses(Request $request)
    {
        $ids = $request->input('id');

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $courses = Course::whereIn('id', $ids)->get();

        if ($courses->isEmpty()) {
            return response()->json([
                'message' => 'Not found any course for delete',
            ], 404);
        }

        foreach ($courses as $course) {
            // Delete cart items that reference this course
            \App\Models\Cart::where('cartable_type', Course::class)
                ->where('cartable_id', $course->id)
                ->delete();
            
            $course->delete();
        }

        return response()->json([
            'message' => 'Success, Course(s) deleted successfully',
        ], 200);
    }


    public function removeFile(Request $request)
    {
        $courseId = $request->input('course_id');
        $fileType = $request->input('file_type');

        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['message' => "Error! course not found."], 404);
        }
        $response = null;
        switch ($fileType) {
            case 'poster':
                $this->removePoster($course);
                $response = ['message' => "Success, poster of the course has been deleted successfully."];
                break;
            case 'trailer':
                $this->removeTrailer($course);
                $response = ['message' => "Success, trailer of the course has been deleted successfully."];
                break;
            case 'attached_file':
                $this->removeAttachedFile($course);
                $response = ['message' => "Success, attached file of the course has been deleted successfully."];
                break;
            default:
                return response()->json(['message' => "Error! Unknown file type.."], 422);
        }
        return response()->json($response, 200);


    }


    public function urlDetails($url)
    {
        if (!Str::is('http*://*', $url)) {
            return;
        }
        $result = [
            'domain' => null,
            'disk' => null,
            'path' => null,
            'size' => null,
            'ext' => null,
            'url' => null,
        ];

        foreach (config('filesystems.disks') as $disk => $config) {
            if (!isset($config['url'])) {
                continue;
            }

            $baseUrl = rtrim($config['url'], '/');

            if (str_starts_with($url, $baseUrl)) {
                $relativePath = ltrim(str_replace($baseUrl, '', $url), '/');
                $result['domain'] = $baseUrl;
                $result['disk'] = $disk;
                $result['path'] = $relativePath;
                $result['size'] = Storage::disk($disk)->size($relativePath);
                $result['ext'] = explode('.', $relativePath)[1];
                $result['url'] = $url;
                break;
            }
        }
        return $result;
    }


}
