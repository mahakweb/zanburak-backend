<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Models\Episode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Services\UploadTokenService;
use Ip2location\IP2LocationLaravel\Facade\IP2LocationLaravel;

class EpisodeController extends Controller
{


    public function createEpisodeData(Course $course)
    {
        $course->load([
            'section.episode' => function ($query) {
                $query->select('id', 'section_id', 'title', 'slug', 'order')->orderBy('order');
            }
        ]);

        return response()->json([
            'message' => 'Success',
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'poster' => $course->poster,
                'type' => $course->type,
                'sections' => $course->section->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'title' => $section->title,
                        'english_title' => $section->english_title,
                        'slug' => $section->slug,
                        'episodes' => $section->episode->map(function ($ep) {
                            return [
                                'id' => $ep->id,
                                'title' => $ep->title,
                                'english_title' => $ep->english_title,
                                'slug' => $ep->slug,
                                'order' => $ep->order,
                            ];
                        })
                    ];
                }),
            ]
        ], 200);
    }

    public function getEpisodeForEdit(Course $course, Section $section, Episode $episode)
    {
        // Verify the episode belongs to the section and course
        if ($episode->section_id !== $section->id || $section->course_id !== $course->id) {
            return response()->json(['message' => 'Episode not found in this course section'], 404);
        }

        // Load episode with videos and attachments
        $episode->load(['videos', 'attachs']);

        // Load course with all sections and episodes for ordering context
        $course->load([
            'section.episode' => function ($query) {
                $query->select('id', 'section_id', 'title', 'slug', 'order')->orderBy('order');
            }
        ]);

        // Get episode video data (similar to course trailer)
        $rawVideo = $episode->videos
            ->where('type', 'raw')
            ->first();

        $videoUrl = null;
        $videoStatus = null;
        $videoId = null;
        if ($rawVideo) {
            $diskUrl = config("filesystems.disks.{$rawVideo->disk}.url");
            if ($diskUrl) {
                $videoUrl = rtrim($diskUrl, '/') . '/' . ltrim($rawVideo->path, '/');
            } else {
                $videoUrl = $rawVideo->path;
            }
            $videoStatus = $rawVideo->status ?? 'queued';
            $videoId = $rawVideo->id;
        }

        // Get episode attachment data
        $rowAttach = $episode->attachs->first();
        $attach = null;
        if ($rowAttach) {
            $attach = [
                'url' => $rowAttach->url,
                'size' => $this->urlDetails($rowAttach->url)['size']
            ];
        }

        return response()->json([
            'message' => 'Success',
            'episode' => [
                'id' => $episode->id,
                'title' => $episode->title,
                'english_title' => $episode->english_title,
                'slug' => $episode->slug,
                'description' => $episode->description,
                'meta_keywords' => $episode->meta_keywords,
                'order' => $episode->order,
                'publish' => $episode->publish,
                'lock' => $episode->lock,
                'publish_date' => $episode->publish_date,
                'section_id' => $episode->section_id,
                'video' => $videoUrl,
                'video_status' => $videoStatus,
                'video_id' => $videoId,
                'attach' => $attach,
            ],
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'poster' => $course->poster,
                'type' => $course->type,
                'sections' => $course->section->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'title' => $section->title,
                        'english_title' => $section->english_title,
                        'slug' => $section->slug,
                        'episodes' => $section->episode->map(function ($ep) {
                            return [
                                'id' => $ep->id,
                                'title' => $ep->title,
                                'english_title' => $ep->english_title,
                                'slug' => $ep->slug,
                                'order' => $ep->order,
                            ];
                        })
                    ];
                }),
            ]
        ], 200);
    }

    public function createNullEpisode(Request $request, Course $course)
    {
        $validator = Validator::make($request->all(), [
            'order' => ['required', 'numeric'],
            'section_id' => ['required', "exists:sections,id"],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            $section = Section::find($request->input('section_id'));
            if (!$section || $section->course_id != $course->id) {
                return response()->json(['message' => 'Validation error!', 'errors' => ['section_id' => ['the selected section not belong to this course.']]], 422);
            }

            $episode = Episode::create([
                'section_id' => $validData['section_id'],
                'order' => $validData['order'],
                'publish' => false,
                'lock' => $course->type == 'free' ? false : true,
            ]);

            $this->reorderCourseEpisodes($course->id);

            return response()->json(['message' => 'Success', 'episode' => $episode], 200);
        }
    }

    public function updateEpisode(Request $request, Course $course)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:5', 'max:255'],
            'english_title' => ['required', 'min:5', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/'],
            'description' => ['nullable'],
            'meta_keywords' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $keywords = array_filter(array_map('trim', explode(',', $value)));
                        $count = count($keywords);
                        
                        if ($count < 3) {
                            $fail('کلمات کلیدی باید حداقل 3 کلمه باشد.');
                        }
                        
                        if ($count > 10) {
                            $fail('کلمات کلیدی نباید بیشتر از 10 کلمه باشد.');
                        }
                        
                        // Check for empty keywords
                        foreach ($keywords as $keyword) {
                            if (empty($keyword)) {
                                $fail('کلمات کلیدی نمی‌تواند خالی باشد.');
                                break;
                            }
                        }
                    }
                },
            ],
            'publish' => ['required', 'boolean'],
            'lock' => ['required', 'boolean'],
            'order' => ['required', 'numeric'],
            'publish_date' => ['nullable', "date"],
            'section_id' => ['required', "exists:sections,id"],
            'episode_id' => ['required', "exists:episodes,id"],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            
            // Clean and format meta_keywords
            if (isset($validData['meta_keywords']) && $validData['meta_keywords']) {
                $keywords = array_filter(array_map('trim', explode(',', $validData['meta_keywords'])));
                $validData['meta_keywords'] = implode(', ', $keywords);
            } else {
                $validData['meta_keywords'] = null;
            }
            
            $section = Section::find($request->input('section_id'));
            if (!$section || $section->course_id != $course->id) {
                return response()->json(['message' => 'Validation error!', 'errors' => ['section_id' => ['the selected section not belong to this course.']]], 422);
            }

            [$minOrder, $maxOrder] = $this->validateEpisodeOrder($validData['section_id'], $validData['order']);

            if ($validData['order'] < $minOrder || $validData['order'] > $maxOrder) {
                return response()->json(['message' => 'Validation error!', 'errors' => ['order' => ["The selected order must be between {$minOrder} and {$maxOrder}."]]], 422);
            }

            $episode = Episode::find($validData['episode_id']);
            if ($episode) {
                $episode->update($validData);
                // if ($episode->order != $validData['order']) {
                    $this->reorderCourseEpisodes($course->id);
                // }

                // ارسال اطلاع‌رسانی به کاربرانی که در دوره ثبت‌نام کرده‌اند
                $enrolledUsers = $course->users;
                foreach ($enrolledUsers as $user) {
                    sendNotification($user, 'course-updated', [
                        'message' => "اپیزود جدیدی به دوره «{$course->title}» اضافه شد.",
                        'subject' => 'به‌روزرسانی دوره',
                        'action_url' => frontendUrl("course/{$course->slug}"),
                        'action_text' => 'مشاهده دوره',
                        'sms_message' => "اپیزود جدیدی به دوره {$course->title} اضافه شد.",
                    ]);
                }

                return response()->json(['message' => 'Success, episode updated successfully.'], 200);
            }


        }
    }

    protected function validateEpisodeOrder(int $sectionId, int $order): array
    {
        $section = Section::with(['course.section.episode'])->findOrFail($sectionId);
        $sections = $section->course->section->sortBy('order')->values();

        $currentIndex = $sections->search(fn($sec) => $sec->id === $section->id);

        $minOrder = 1;
        for ($i = $currentIndex - 1; $i >= 0; $i--) {
            $prevOrders = $sections[$i]->episode->pluck('order')->all();
            if (!empty($prevOrders)) {
                $minOrder = max($prevOrders) + 1;
                break;
            }
        }

        $maxOrder = null;
        for ($i = $currentIndex + 1; $i < count($sections); $i++) {
            $nextOrders = $sections[$i]->episode->pluck('order')->all();
            if (!empty($nextOrders)) {
                $maxOrder = min($nextOrders);
                break;
            }
        }

        if (is_null($maxOrder)) {
            $allOrders = $sections->flatMap(fn($s) => $s->episode->pluck('order'))->all();
            $maxOrder = !empty($allOrders) ? max($allOrders) + 1 : 1;
        }

        return [$minOrder, $maxOrder];
    }

    protected function reorderCourseEpisodes(int $courseId): void
    {
        $episodes = Episode::whereHas('section', fn($q) => $q->where('course_id', $courseId))
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($episodes as $index => $episode) {
            $episode->update(['order' => $index + 1]);
        }
    }

    public function uploadAttachedFile(Request $request, Course $course)
    {
        $validator = Validator::make($request->all(), [
            'episode_id' => ['required', 'exists:episodes,id'],
            'filename' => ['required', 'string'],
            'mime' => ['required', 'string'],
            'size' => ['required', 'integer', 'min:1'],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();
            $episode = Episode::findOrFail($validData['episode_id']);
            if (!$episode) {
                return response()->json(['message' => 'Error! episode not found'], 404);
            }
            if ($episode->section->course->id != $course->id) {
                return response()->json(['message' => 'Validation error!', 'errors' => ['episode_id' => ['the selected episode not belong to this course.']]], 422);
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
                'episodeId' => $episode->id,
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

    public function removeAttachedFile($episode)
    {
        $attach = $episode->attachs->first();
        if ($attach) {
            $details = $this->urlDetails($attach->url);
            if ($details && Storage::disk($details['disk'])->exists($details['path'])) {
                Storage::disk($details['disk'])->delete($details['path']);
                $episode->attachs()->delete();
            }
        }
    }

    public function removeVideo($episode)
    {
        $videos = $episode->videos;
        if ($videos) {
            foreach ($videos as $vid) {
                $folderPath = dirname($vid->path);
                $storage = Storage::disk($vid->disk);
                if ($vid->type == 'raw' && Storage::disk($vid->disk)->exists($vid->path)) {
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
            $episode->videos()->delete();
            
            // Update episode total_time to 0 after removing videos
            $episode->update(['total_time' => 0]);
        }
    }

    public function removeFile(Request $request)
    {
        $episodeId = $request->input('episode_id');
        $fileType = $request->input('file_type');

        $episode = Episode::find($episodeId);
        if (!$episode) {
            return response()->json(['message' => "Error! episode not found."], 404);
        }
        $response = null;
        switch ($fileType) {
            case 'video':
                $this->removeVideo($episode);
                $response = ['message' => "Success, video of the episode has been deleted successfully."];
                break;
            case 'attached_file':
                $this->removeAttachedFile($episode);
                $response = ['message' => "Success, attached file of the episode has been deleted successfully."];
                break;
            default:
                return response()->json(['message' => "Error! Unknown file type.."], 422);
        }
        return response()->json($response, 200);
    }

    public function deleteEpisode(Request $request, Course $course, Episode $episode)
    {
        // Verify the episode belongs to the course
        if ($episode->section->course_id != $course->id) {
            return response()->json(['message' => 'error! this episode not belong to selected course'], 422);
        }

        $deletedEpisode = $episode;
        $episode->delete();
        
        // Reorder remaining episodes
        $this->reorderCourseEpisodes($course->id);
        
        return response()->json(['message' => 'success, episode deleted successfully.', 'episode' => $deletedEpisode], 200);
    }

    public function getEpisodeDetails(Request $request, Course $course, Episode $episode)
    {
        // Verify the episode belongs to the course
        if ($episode->section->course_id != $course->id) {
            return response()->json(['message' => 'error! this episode not belong to selected course'], 422);
        }

        // Get pagination parameters
        $dataType = $request->input('data_type', 'overview'); // overview, comments, likes, bookmarks, views, videos
        $perPage = (int) $request->input('perPage', 20);
        $currentPage = (int) $request->input('page', 1);
        $sort = $request->input('sort', 'desc'); // desc, asc
        $filter = $request->input('filter', 'all'); // For comments: all, approved, unapproved
        $viewMode = $request->input('viewMode', 'table'); // table | grid

        // Load episode basic info
        $episode->load([
            'section' => function ($query) {
                $query->select('id', 'title', 'slug', 'course_id');
            },
            'section.course' => function ($query) {
                $query->select('id', 'title', 'slug');
            },
            'videos' => function ($query) {
                $query->select('id', 'videoable_id', 'videoable_type', 'type', 'path', 'disk', 'status', 'duration', 'created_at');
            },
            'attachs' => function ($query) {
                $query->select('id', 'attachable_id', 'attachable_type', 'url', 'title', 'created_at');
            }
        ]);

        // Get counts
        $likesCount = $episode->likes()->count();
        $commentsCount = $episode->comments()->where('approved', 1)->count();
        $viewsCount = $episode->views()->count();
        $bookmarksCount = $episode->bookmarkersCount();

        $response = [
            'message' => 'Success',
            'episode' => [
                'id' => $episode->id,
                'title' => $episode->title,
                'english_title' => $episode->english_title,
                'slug' => $episode->slug,
                'description' => $episode->description,
                'meta_keywords' => $episode->meta_keywords,
                'order' => $episode->order,
                'publish' => $episode->publish,
                'lock' => $episode->lock,
                'publish_date' => $episode->publish_date,
                'total_time' => $episode->total_time,
                'created_at' => $episode->created_at,
                'updated_at' => $episode->updated_at,
                'section' => [
                    'id' => $episode->section->id,
                    'title' => $episode->section->title,
                    'slug' => $episode->section->slug,
                ],
                'course' => $episode->section->course ? [
                    'id' => $episode->section->course->id,
                    'title' => $episode->section->course->title,
                    'slug' => $episode->section->course->slug,
                ] : null,
            ],
            'statistics' => [
                'likes_count' => $likesCount,
                'comments_count' => $commentsCount,
                'views_count' => $viewsCount,
                'bookmarks_count' => $bookmarksCount,
            ],
        ];

        // Handle paginated data based on data_type
        switch ($dataType) {
            case 'comments':
                $sortOrder = $sort === 'asc' ? 'asc' : 'desc';
                
                if ($viewMode === 'grid') {
                    // Grid view: Get only parent comments with their children
                    $commentsQuery = $episode->comments()->where('parent_id', 0)
                        ->with([
                            'user' => function ($q) {
                                $q->select('id', 'first_name', 'last_name', 'username', 'profile_pic');
                            },
                            'childs' => function ($q) use ($filter, $sortOrder) {
                                $childQuery = $q->with([
                                    'user' => function ($u) {
                                        $u->select('id', 'first_name', 'last_name', 'username', 'profile_pic');
                                    }
                                ]);
                                
                                if ($filter === 'approved') {
                                    $childQuery->where('approved', 1);
                                } elseif ($filter === 'unapproved') {
                                    $childQuery->where('approved', 0);
                                }
                                
                                $childQuery->orderBy('created_at', $sortOrder);
                            }
                        ]);

                    if ($filter === 'approved') {
                        $commentsQuery->where('approved', 1);
                    } elseif ($filter === 'unapproved') {
                        $commentsQuery->where('approved', 0);
                    }

                    $commentsQuery->orderBy('created_at', $sortOrder);

                    $total = $commentsQuery->count();
                    $lastPage = ceil($total / $perPage);
                    $comments = $commentsQuery->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

                    $response['comments'] = $comments->map(function ($comment) {
                        return [
                            'id' => $comment->id,
                            'comment' => $comment->comment,
                            'approved' => $comment->approved,
                            'created_at' => $comment->created_at,
                            'user' => $comment->user ? [
                                'id' => $comment->user->id,
                                'first_name' => $comment->user->first_name,
                                'last_name' => $comment->user->last_name,
                                'name' => $comment->user->first_name . ' ' . $comment->user->last_name,
                                'username' => $comment->user->username,
                                'profile_pic' => $comment->user->profile_pic,
                            ] : null,
                            'replies' => $comment->childs->map(function ($reply) {
                                return [
                                    'id' => $reply->id,
                                    'comment' => $reply->comment,
                                    'approved' => $reply->approved,
                                    'created_at' => $reply->created_at,
                                    'user' => $reply->user ? [
                                        'id' => $reply->user->id,
                                        'first_name' => $reply->user->first_name,
                                        'last_name' => $reply->user->last_name,
                                        'name' => $reply->user->first_name . ' ' . $reply->user->last_name,
                                        'username' => $reply->user->username,
                                        'profile_pic' => $reply->user->profile_pic,
                                    ] : null,
                                ];
                            })->values(),
                        ];
                    });
                } else {
                    // Table view: Get all comments flat (including replies) sorted by time
                    $commentsQuery = $episode->comments()
                        ->with([
                            'user' => function ($q) {
                                $q->select('id', 'first_name', 'last_name', 'username', 'profile_pic');
                            },
                            'parent' => function ($q) {
                                $q->with([
                                    'user' => function ($u) {
                                        $u->select('id', 'first_name', 'last_name', 'username', 'profile_pic');
                                    }
                                ]);
                            }
                        ]);

                    if ($filter === 'approved') {
                        $commentsQuery->where('approved', 1);
                    } elseif ($filter === 'unapproved') {
                        $commentsQuery->where('approved', 0);
                    }

                    $commentsQuery->orderBy('created_at', $sortOrder);

                    $total = $commentsQuery->count();
                    $lastPage = ceil($total / $perPage);
                    $comments = $commentsQuery->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

                    $response['comments'] = $comments->map(function ($comment) {
                        $base = [
                            'id' => $comment->id,
                            'comment' => $comment->comment,
                            'approved' => $comment->approved,
                            'created_at' => $comment->created_at,
                            'parent_id' => $comment->parent_id,
                            'user' => $comment->user ? [
                                'id' => $comment->user->id,
                                'first_name' => $comment->user->first_name,
                                'last_name' => $comment->user->last_name,
                                'name' => $comment->user->first_name . ' ' . $comment->user->last_name,
                                'username' => $comment->user->username,
                                'profile_pic' => $comment->user->profile_pic,
                            ] : null,
                        ];

                        // Parent comment (if this is a reply)
                        $base['parent'] = null;
                        if ($comment->parent) {
                            $base['parent'] = [
                                'id' => $comment->parent->id,
                                'comment' => $comment->parent->comment,
                                'approved' => $comment->parent->approved,
                                'created_at' => $comment->parent->created_at,
                                'user' => $comment->parent->user ? [
                                    'id' => $comment->parent->user->id,
                                    'first_name' => $comment->parent->user->first_name,
                                    'last_name' => $comment->parent->user->last_name,
                                    'name' => $comment->parent->user->first_name . ' ' . $comment->parent->user->last_name,
                                    'username' => $comment->parent->user->username,
                                    'profile_pic' => $comment->parent->user->profile_pic,
                                ] : null,
                            ];
                        }

                        return $base;
                    });
                }

                $response['pagination'] = [
                    'total' => $total,
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'last_page' => $lastPage,
                    'from' => ($currentPage - 1) * $perPage + 1,
                    'to' => min($currentPage * $perPage, $total),
                ];
                break;

            case 'likes':
                $likesQuery = $episode->likes()->with([
                    'user' => function ($q) {
                        $q->select('id', 'first_name', 'last_name', 'username', 'profile_pic');
                    }
                ]);

                if ($filter !== 'all') {
                    $likesQuery->where('type', $filter);
                }

                $sortOrder = $sort === 'asc' ? 'asc' : 'desc';
                $likesQuery->orderBy('created_at', $sortOrder);

                $total = $likesQuery->count();
                $lastPage = ceil($total / $perPage);
                $likes = $likesQuery->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

                $response['likes'] = $likes->map(function ($like) {
                    return [
                        'id' => $like->id,
                        'type' => $like->type,
                        'created_at' => $like->created_at,
                        'user' => $like->user ? [
                            'id' => $like->user->id,
                            'name' => $like->user->first_name . ' ' . $like->user->last_name,
                            'username' => $like->user->username,
                            'profile_pic' => $like->user->profile_pic,
                        ] : null,
                    ];
                });

                $response['pagination'] = [
                    'total' => $total,
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'last_page' => $lastPage,
                    'from' => ($currentPage - 1) * $perPage + 1,
                    'to' => min($currentPage * $perPage, $total),
                ];
                break;

            case 'bookmarks':
                $bookmarksQuery = DB::table('bookmarks')
                    ->where('bookmarkable_type', Episode::class)
                    ->where('bookmarkable_id', $episode->id)
                    ->join('users', 'bookmarks.user_id', '=', 'users.id')
                    ->select('bookmarks.id', 'bookmarks.created_at', 'users.id as user_id', 'users.first_name', 'users.last_name', 'users.username', 'users.profile_pic');

                $sortOrder = $sort === 'asc' ? 'asc' : 'desc';
                $bookmarksQuery->orderBy('bookmarks.created_at', $sortOrder);

                $total = $bookmarksQuery->count();
                $lastPage = ceil($total / $perPage);
                $bookmarks = $bookmarksQuery->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

                $response['bookmarks'] = $bookmarks->map(function ($bookmark) {
                    return [
                        'id' => $bookmark->id,
                        'created_at' => $bookmark->created_at,
                        'user' => [
                            'id' => $bookmark->user_id,
                            'name' => ($bookmark->first_name ?? '') . ' ' . ($bookmark->last_name ?? ''),
                            'username' => $bookmark->username,
                            'profile_pic' => $bookmark->profile_pic,
                        ],
                    ];
                });

                $response['pagination'] = [
                    'total' => $total,
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'last_page' => $lastPage,
                    'from' => ($currentPage - 1) * $perPage + 1,
                    'to' => min($currentPage * $perPage, $total),
                ];
                break;

            case 'views':
                $viewsQuery = $episode->views()->with([
                    'user' => function ($q) {
                        $q->select('id', 'first_name', 'last_name', 'username', 'profile_pic');
                    }
                ]);

                $sortOrder = $sort === 'asc' ? 'asc' : 'desc';
                $viewsQuery->orderBy('created_at', $sortOrder);

                $total = $viewsQuery->count();
                $lastPage = ceil($total / $perPage);
                $views = $viewsQuery->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

                $response['views'] = $views->map(function ($view) {
                    $ipInfo = collect(IP2LocationLaravel::get($view->ip_address))->only(['countryName', 'countryCode', 'cityName', 'regionName']);
                    return [
                        'id' => $view->id,
                        'ip_address' => $view->ip_address,
                        'user_agent' => $view->user_agent,
                        'created_at' => $view->created_at,
                        'ipInfo' => $ipInfo,
                        'user' => $view->user ? [
                            'id' => $view->user->id,
                            'name' => $view->user->first_name . ' ' . $view->user->last_name,
                            'username' => $view->user->username,
                            'profile_pic' => $view->user->profile_pic,
                        ] : null,
                    ];
                });

                $response['pagination'] = [
                    'total' => $total,
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'last_page' => $lastPage,
                    'from' => ($currentPage - 1) * $perPage + 1,
                    'to' => min($currentPage * $perPage, $total),
                ];
                break;

            case 'videos':
                $videosQuery = $episode->videos();

                $sortOrder = $sort === 'asc' ? 'asc' : 'desc';
                $videosQuery->orderBy('created_at', $sortOrder);

                $total = $videosQuery->count();
                $lastPage = ceil($total / $perPage);
                $videos = $videosQuery->skip(($currentPage - 1) * $perPage)->take($perPage)->get();

                $response['videos'] = $videos->map(function ($video) {
                    $diskUrl = config("filesystems.disks.{$video->disk}.url");
                    $videoUrl = null;
                    if ($diskUrl) {
                        $videoUrl = rtrim($diskUrl, '/') . '/' . ltrim($video->path, '/');
                    } else {
                        $videoUrl = $video->path;
                    }

                    $fileSize = null;
                    if (Storage::disk($video->disk)->exists($video->path)) {
                        $fileSize = Storage::disk($video->disk)->size($video->path);
                    }

                    return [
                        'id' => $video->id,
                        'type' => $video->type,
                        'status' => $video->status,
                        'path' => $video->path,
                        'disk' => $video->disk,
                        'url' => $videoUrl,
                        'duration' => $video->duration,
                        'quality' => $video->quality,
                        'size' => $fileSize,
                        'created_at' => $video->created_at,
                    ];
                });

                $response['pagination'] = [
                    'total' => $total,
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'last_page' => $lastPage,
                    'from' => ($currentPage - 1) * $perPage + 1,
                    'to' => min($currentPage * $perPage, $total),
                ];
                break;

            case 'analytics':
                // Get detailed analytics data
                $allViews = $episode->views()->get();
                $allLikes = $episode->likes()->get();
                $allComments = $episode->comments()->get();
                $allBookmarks = $episode->bookmarkableBookmarks()->get();
                
                // Views by date (last 30 days)
                $viewsByDate = [];
                for ($i = 29; $i >= 0; $i--) {
                    $date = now()->subDays($i)->format('Y-m-d');
                    $count = $allViews->filter(function ($view) use ($date) {
                        return $view->created_at->format('Y-m-d') === $date;
                    })->count();
                    $viewsByDate[] = ['date' => $date, 'count' => $count];
                }
                
                // Likes by date (last 30 days)
                $likesByDate = [];
                for ($i = 29; $i >= 0; $i--) {
                    $date = now()->subDays($i)->format('Y-m-d');
                    $count = $allLikes->filter(function ($like) use ($date) {
                        return $like->created_at->format('Y-m-d') === $date;
                    })->count();
                    $likesByDate[] = ['date' => $date, 'count' => $count];
                }
                
                // Comments by date (last 30 days)
                $commentsByDate = [];
                for ($i = 29; $i >= 0; $i--) {
                    $date = now()->subDays($i)->format('Y-m-d');
                    $count = $allComments->filter(function ($comment) use ($date) {
                        return $comment->created_at->format('Y-m-d') === $date;
                    })->count();
                    $commentsByDate[] = ['date' => $date, 'count' => $count];
                }
                
                // Bookmarks by date (last 30 days)
                $bookmarksByDate = [];
                for ($i = 29; $i >= 0; $i--) {
                    $date = now()->subDays($i)->format('Y-m-d');
                    $count = $allBookmarks->filter(function ($bookmark) use ($date) {
                        return $bookmark->created_at->format('Y-m-d') === $date;
                    })->count();
                    $bookmarksByDate[] = ['date' => $date, 'count' => $count];
                }
                
                // Views by hour
                $viewsByHour = [];
                for ($hour = 0; $hour < 24; $hour++) {
                    $count = $allViews->filter(function ($view) use ($hour) {
                        return (int)$view->created_at->format('H') === $hour;
                    })->count();
                    $viewsByHour[] = ['hour' => $hour, 'count' => $count];
                }
                
                // Engagement rate (likes + comments + bookmarks) / views
                $engagementRate = $viewsCount > 0 
                    ? round((($likesCount + $commentsCount + $bookmarksCount) / $viewsCount) * 100, 2) 
                    : 0;
                
                // Average views per day (since episode creation)
                $daysSinceCreation = max(1, now()->diffInDays($episode->created_at));
                $avgViewsPerDay = round($viewsCount / $daysSinceCreation, 2);
                
                // Growth rate (last 7 days vs previous 7 days)
                $last7DaysViews = $allViews->filter(function ($view) {
                    return $view->created_at->isAfter(now()->subDays(7));
                })->count();
                $previous7DaysViews = $allViews->filter(function ($view) {
                    return $view->created_at->isAfter(now()->subDays(14)) && 
                           $view->created_at->isBefore(now()->subDays(7));
                })->count();
                $viewsGrowthRate = $previous7DaysViews > 0 
                    ? round((($last7DaysViews - $previous7DaysViews) / $previous7DaysViews) * 100, 2) 
                    : ($last7DaysViews > 0 ? 100 : 0);
                
                $response['analytics'] = [
                    'views_by_date' => $viewsByDate,
                    'likes_by_date' => $likesByDate,
                    'comments_by_date' => $commentsByDate,
                    'bookmarks_by_date' => $bookmarksByDate,
                    'views_by_hour' => $viewsByHour,
                    'engagement_rate' => $engagementRate,
                    'avg_views_per_day' => $avgViewsPerDay,
                    'views_growth_rate' => $viewsGrowthRate,
                    'days_since_creation' => $daysSinceCreation,
                ];
                break;

            case 'overview':
            default:
                // Return basic overview without pagination
                $response['videos'] = $episode->videos->map(function ($video) {
                    return [
                        'id' => $video->id,
                        'type' => $video->type,
                        'status' => $video->status,
                    ];
                });
                $response['attachs'] = $episode->attachs->map(function ($attach) {
                    $details = $this->urlDetails($attach->url);
                    return [
                        'id' => $attach->id,
                        'url' => $attach->url,
                        'title' => $attach->title ?? null,
                        'size' => $details ? ($details['size'] ?? null) : null,
                        'ext' => $details ? ($details['ext'] ?? null) : null,
                        'created_at' => $attach->created_at,
                    ];
                });
                
                // Get raw video URL for player
                $rawVideo = $episode->videos->where('type', 'raw')->first();
                $rawVideoUrl = null;
                if ($rawVideo) {
                    $diskUrl = config("filesystems.disks.{$rawVideo->disk}.url");
                    if ($diskUrl) {
                        $rawVideoUrl = rtrim($diskUrl, '/') . '/' . ltrim($rawVideo->path, '/');
                    } else {
                        $rawVideoUrl = $rawVideo->path;
                    }
                }
                $response['raw_video_url'] = $rawVideoUrl;
                break;
        }

        return response()->json($response, 200);
    }

    public function episodeStatus(Request $request, Course $course)
    {
        $validator = Validator::make($request->all(), [
            'episode_id' => ['required', 'exists:episodes,id'],
            'publish' => ['nullable', 'boolean'],
            'lock' => ['nullable', 'boolean'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }

        $episode = Episode::find($request->input('episode_id'));
        if (!$episode) {
            return response()->json(['message' => 'Episode not found'], 404);
        }

        // Verify the episode belongs to the course
        if ($episode->section->course_id != $course->id) {
            return response()->json(['message' => 'error! this episode not belong to selected course'], 422);
        }

        $updateData = [];
        if ($request->has('publish')) {
            $updateData['publish'] = $request->input('publish');
        }
        if ($request->has('lock')) {
            $updateData['lock'] = $request->input('lock');
        }

        if (!empty($updateData)) {
            $episode->update($updateData);
        }

        return response()->json([
            'message' => 'Success',
            'episode' => [
                'id' => $episode->id,
                'publish' => $episode->publish,
                'lock' => $episode->lock,
            ]
        ], 200);
    }

    private function urlDetails($url)
    {
        if (!Str::is('http*://*', $url)) {
            return null;
        }

        foreach (config('filesystems.disks') as $disk => $config) {
            if (!isset($config['url'])) {
                continue;
            }

            $baseUrl = rtrim($config['url'], '/');
            if (str_starts_with($url, $baseUrl)) {
                $relativePath = ltrim(str_replace($baseUrl, '', $url), '/');
                $size = null;
                if (Storage::disk($disk)->exists($relativePath)) {
                    $size = Storage::disk($disk)->size($relativePath);
                }
                return [
                    'domain' => $baseUrl,
                    'disk' => $disk,
                    'path' => $relativePath,
                    'size' => $size,
                    'ext' => pathinfo($relativePath, PATHINFO_EXTENSION),
                    'url' => $url,
                ];
            }
        }

        return null;
    }

}