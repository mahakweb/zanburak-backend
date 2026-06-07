<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Course;
use App\Models\DiscountEligibility;
use App\Models\Path;
use App\Models\PathAutomationRule;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PathController extends Controller
{
    /**
     * List learning paths for admin with related items and counts
     */
    public function paths(Request $request)
    {
        $perPage = (int) $request->input('perPage', 20);
        $statusFilter = $request->input('status'); // all | active | inactive
        $search = $request->input('search');
        $sort = $request->input('sort', 'newest'); // newest | oldest | courses_high

        $query = Path::query()
            ->select(['id', 'title', 'english_title', 'slug', 'icon', 'poster', 'faqs', 'status', 'created_at'])
            ->with([
                'courses:id,title,slug',
                'prerequisites:id,title,slug',
                'nextSteps:id,title,slug',
            ])
            ->withCount(['courses', 'prerequisites', 'nextSteps']);

        if ($statusFilter === 'active') {
            $query->where('status', 1);
        } elseif ($statusFilter === 'inactive') {
            $query->where('status', 0);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('english_title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'courses_high':
                $query->orderBy('courses_count', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $paginator = $query->paginate($perPage);

        $paths = $paginator->map(function (Path $path) {
            return [
                'id' => $path->id,
                'title' => $path->title,
                'english_title' => $path->english_title,
                'slug' => $path->slug,
                'icon' => $path->icon,
                'poster' => $path->poster,
                'faqs' => $path->faqs,
                'status' => (bool) $path->status,
                'created_at' => $path->created_at,
                'courses_count' => $path->courses_count,
                'prerequisites_count' => $path->prerequisites_count,
                'next_steps_count' => $path->next_steps_count,
                'courses' => $path->courses->map(function ($course) {
                    return [
                        'id' => $course->id,
                        'title' => $course->title,
                        'slug' => $course->slug,
                    ];
                }),
                'prerequisites' => $path->prerequisites->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'title' => $p->title,
                        'slug' => $p->slug,
                    ];
                }),
                'next_steps' => $path->nextSteps->map(function ($n) {
                    return [
                        'id' => $n->id,
                        'title' => $n->title,
                        'slug' => $n->slug,
                    ];
                }),
            ];
        });

        return response()->json([
            'message' => 'Success',
            'paths' => $paths,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ], 200);
    }

    /**
     * Get path data for edit by slug or id
     */
    public function edit(Request $request, Path $path)
    {
        $path->load([
            'courses:id,title,english_title,slug',
            'prerequisites:id,title,slug',
            'nextSteps:id,title,slug',
            'corequisites:id,title,slug',
            'automationRules',
            'videos' // ensure relation exists on Path model
        ]);

        // Build trailer like course: prefer raw video from videos relation
        $rawTrailer = $path->videos ? $path->videos->where('type', 'raw')->first() : null;
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
            $trailerUrl = $path->trailer;
        }

        return response()->json([
            'message' => 'Success',
            'path' => [
                'id' => $path->id,
                'title' => $path->title,
                'english_title' => $path->english_title,
                'short_description' => $path->short_description,
                'description' => $path->description,
                'meta_keywords' => $path->meta_keywords,
                'icon' => $path->icon,
                'poster' => $path->poster,
                'trailer' => $trailerUrl,
                'trailer_status' => $trailerStatus,
                'trailer_video_id' => $trailerVideoId,
                'faqs' => $path->faqs,
                'status' => (bool) $path->status,
                'slug' => $path->slug,
                // associations
                'courses' => $path->courses->map(fn($c) => ['id' => $c->id, 'title' => $c->title, 'slug' => $c->slug])->values(),
                'prerequisites' => $path->prerequisites->map(fn($p) => ['id' => $p->id, 'title' => $p->title, 'slug' => $p->slug])->values(),
                'next_steps' => $path->nextSteps->map(fn($n) => ['id' => $n->id, 'title' => $n->title, 'slug' => $n->slug])->values(),
                'corequisites' => $path->corequisites->map(fn($n) => ['id' => $n->id, 'title' => $n->title, 'slug' => $n->slug])->values(),
                // assignment rules
                'assignment_type' => $path->automationRules()->exists() ? 'automatic' : 'manual',
                'match_type' => optional($path->automationRules()->first())->match_type,
                'rules' => $path->automationRules->map(function($r){ return [
                    'target' => $r->target,
                    'field' => $r->field,
                    'operator' => $r->operator,
                    'value' => $r->value,
                    'match_type' => $r->match_type,
                ]; })->values(),
            ],
        ], 200);
    }

    /**
     * Update path
     */
    public function update(Request $request, Path $path)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'min:3', 'max:255', Rule::unique('paths', 'title')->ignore($path->id)],
            'english_title' => [
                'required', 'string', 'min:3', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\\]\\{}|;":",.\/<>>?a-zA-Z0-9- ]+$/',
                Rule::unique('paths', 'english_title')->ignore($path->id)
            ],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
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
            'icon' => ['nullable', 'string', 'max:1024'],
            'poster' => ['nullable', 'string', 'max:1024'],
            'trailer' => ['nullable', 'string', 'max:1024'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['required_with:faqs', 'string', 'max:500'],
            'faqs.*.answer' => ['required_with:faqs', 'string', 'max:2000'],
            'status' => ['nullable', 'boolean'],
            'assignment_type' => ['nullable', Rule::in(['manual', 'automatic'])],
            'match_type' => ['nullable', Rule::in(['all', 'any'])],
            'rules' => ['nullable', 'array'],
            'rules.*.field' => ['nullable', 'string'],
            'rules.*.operator' => ['nullable', 'string'],
            'rules.*.value' => ['nullable'],
            'courses' => ['nullable', 'array'],
            'courses.*' => ['integer', 'exists:courses,id'],
            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['integer', 'exists:paths,id'],
            'next_steps' => ['nullable', 'array'],
            'next_steps.*' => ['integer', 'exists:paths,id'],
            'corequisites' => ['nullable', 'array'],
            'corequisites.*' => ['integer', 'exists:paths,id'],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }
        $valid = $validator->validated();

        $path->title = $valid['title'];
        $path->english_title = $valid['english_title'];
        $path->short_description = $valid['short_description'] ?? null;
        $path->description = $valid['description'] ?? null;
        
        // Clean and format meta_keywords
        if (isset($valid['meta_keywords']) && $valid['meta_keywords']) {
            $keywords = array_filter(array_map('trim', explode(',', $valid['meta_keywords'])));
            $path->meta_keywords = implode(', ', $keywords);
        } else {
            $path->meta_keywords = null;
        }
        if (array_key_exists('icon', $valid)) $path->icon = $valid['icon'];
        if (array_key_exists('poster', $valid)) $path->poster = $valid['poster'];
        if (array_key_exists('trailer', $valid)) $path->trailer = $valid['trailer'];
        $path->faqs = $valid['faqs'] ?? null;
        if (array_key_exists('status', $valid)) $path->status = (bool) $valid['status'];

        $path->save();

        // Update associations
        if (!empty($valid['courses'])) {
            $path->courses()->sync($valid['courses']);
        } elseif (array_key_exists('courses', $valid)) {
            $path->courses()->sync([]);
        }

        if (!empty($valid['prerequisites'])) {
            $path->prerequisites()->syncWithPivotValues($valid['prerequisites'], ['type' => 'prerequisite']);
        } elseif (array_key_exists('prerequisites', $valid)) {
            $path->prerequisites()->detach();
        }

        if (!empty($valid['next_steps'])) {
            $path->nextSteps()->syncWithPivotValues($valid['next_steps'], ['type' => 'next']);
        } elseif (array_key_exists('next_steps', $valid)) {
            $path->nextSteps()->detach();
        }

        if (!empty($valid['corequisites'])) {
            $path->corequisites()->syncWithPivotValues($valid['corequisites'], ['type' => 'corequisite']);
        } elseif (array_key_exists('corequisites', $valid)) {
            $path->corequisites()->detach();
        }

        // Update automation rules
        if (($valid['assignment_type'] ?? null) === 'automatic') {
            $path->automationRules()->where('target', 'course')->delete();
            foreach (($valid['rules'] ?? []) as $rule) {
                $path->automationRules()->create([
                    'target' => 'course',
                    'match_type' => $valid['match_type'] ?? 'any',
                    'field' => $rule['field'] ?? '',
                    'operator' => $rule['operator'] ?? '',
                    'value' => $rule['value'] ?? '',
                ]);
            }
            // Sync courses according to rules
            $this->syncPathAssignments($path);
        } elseif (array_key_exists('assignment_type', $valid) && $valid['assignment_type'] === 'manual') {
            $path->automationRules()->where('target', 'course')->delete();
        }

        return response()->json([
            'message' => 'Success, path updated successfully.',
            'path' => [ 'id' => $path->id, 'slug' => $path->slug ]
        ], 200);
    }

    /**
     * Delete one or more paths
     */
    public function deletePaths(Request $request)
    {
        $slugs = $request->input('slug');

        if (!is_array($slugs)) {
            $slugs = [$slugs];
        }

        $slugs = array_values(array_unique(array_filter(array_map('strval', $slugs))));

        if (empty($slugs)) {
            return response()->json([
                'message' => 'Validation error!',
                'errors' => ['slug' => ['شناسه مسیر الزامی است.']],
            ], 422);
        }

        $paths = Path::whereIn('slug', $slugs)->get();

        if ($paths->isEmpty()) {
            return response()->json([
                'message' => 'مسیر یافت نشد.',
            ], 404);
        }

        $deletedIds = [];
        $skipped = [];

        foreach ($paths as $path) {
            try {
                DB::transaction(function () use ($path) {
                    Cart::where('cartable_type', Path::class)
                        ->where('cartable_id', $path->id)
                        ->delete();

                    DiscountEligibility::where(function ($query) {
                        $query->where('target_type', 'path')
                            ->orWhere('target_type', Path::class);
                    })
                        ->where('target_id', $path->id)
                        ->delete();

                    Report::where('reportable_type', Path::class)
                        ->where('reportable_id', $path->id)
                        ->delete();

                    DB::table(config('subscribe.subscriptions_table', 'subscriptions'))
                        ->where('subscribable_type', Path::class)
                        ->where('subscribable_id', $path->id)
                        ->delete();

                    $path->courses()->detach();
                    $path->prerequisites()->detach();
                    $path->nextSteps()->detach();
                    $path->corequisites()->detach();
                    $path->prerequisiteFor()->detach();
                    $path->previousSteps()->detach();

                    $path->delete();
                });

                $deletedIds[] = $path->id;
            } catch (\Throwable $e) {
                Log::error('Admin path delete failed', [
                    'path_id' => $path->id,
                    'error' => $e->getMessage(),
                ]);
                $skipped[] = $path->title;
            }
        }

        if (empty($deletedIds)) {
            return response()->json([
                'message' => 'حذف مسیرها انجام نشد.',
                'skipped' => $skipped,
            ], 409);
        }

        return response()->json([
            'message' => 'مسیر(ها) با موفقیت حذف شدند.',
            'deleted_ids' => $deletedIds,
            'deleted_slugs' => $paths->whereIn('id', $deletedIds)->pluck('slug')->values(),
            'skipped' => $skipped,
        ], 200);
    }

    /**
     * Delete path by slug (legacy endpoint)
     */
    public function destroy(Request $request, Path $path)
    {
        $request->merge(['slug' => $path->slug]);

        return $this->deletePaths($request);
    }

    /**
     * Remove a specific file from path (icon/poster/trailer)
     */
    public function removeFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path_id' => ['required', 'exists:paths,id'],
            'file_type' => ['required', Rule::in(['trailer', 'poster', 'icon'])],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }
        $valid = $validator->validated();
        $path = Path::findOrFail($valid['path_id']);

        switch ($valid['file_type']) {
            case 'trailer':
                if (!empty($path->trailer)) $this->removeUrl($path->trailer);
                // Also remove videos relation if any
                if ($path->videos && $path->videos->count()) {
                    foreach ($path->videos as $vid) {
                        if (!empty($vid->path)) {
                            $diskUrl = config("filesystems.disks.{$vid->disk}.url");
                            $filePath = $vid->path;
                            if ($diskUrl && str_starts_with($filePath, rtrim($diskUrl, '/'))) {
                                $filePath = ltrim(str_replace(rtrim($diskUrl, '/'), '', $filePath), '/');
                            }
                            if (Storage::disk($vid->disk)->exists($filePath)) {
                                Storage::disk($vid->disk)->delete($filePath);
                            }
                        }
                        $vid->delete();
                    }
                }
                $path->trailer = null;
                break;
            case 'poster':
                if (!empty($path->poster)) $this->removeUrl($path->poster);
                $path->poster = null;
                break;
            case 'icon':
                if (!empty($path->icon)) $this->removeUrl($path->icon);
                $path->icon = null;
                break;
        }
        $path->save();

        return response()->json(['message' => 'Success, file has been removed.'], 200);
    }
    /**
     * Search courses by q
     */
    public function searchCourses(Request $request)
    {
        $q = $request->input('q', '');
        $perPage = (int) $request->input('perPage', 10);

        $query = Course::query()
            ->select(['id', 'title', 'english_title', 'slug'])
            ->when($q, function ($builder) use ($q) {
                $builder->where(function ($s) use ($q) {
                    $s->where('title', 'like', "%{$q}%")
                        ->orWhere('english_title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->orderBy('id', 'desc');

        $results = $query->paginate($perPage);

        return response()->json([
            'message' => 'Success',
            'items' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ], 200);
    }

    /**
     * Search paths by q
     */
    public function searchPaths(Request $request)
    {
        $q = $request->input('q', '');
        $perPage = (int) $request->input('perPage', 10);

        $query = Path::query()
            ->select(['id', 'title', 'english_title', 'slug'])
            ->when($q, function ($builder) use ($q) {
                $builder->where(function ($s) use ($q) {
                    $s->where('title', 'like', "%{$q}%")
                        ->orWhere('english_title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->orderBy('id', 'desc');

        $results = $query->paginate($perPage);

        return response()->json([
            'message' => 'Success',
            'items' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ], 200);
    }

    /**
     * Create a new learning path (manual or automatic rules)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'min:3', 'max:255', 'unique:paths,title'],
            'english_title' => ['required', 'string', 'min:3', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\\]\\{}|;":",.\/<>>?a-zA-Z0-9- ]+$/', 'unique:paths,english_title'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'meta_keywords' => [
                'required',
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
            'icon' => ['required', 'string', 'max:1024'],
            'poster' => ['required', 'string', 'max:1024'],
            'trailer' => ['nullable', 'string', 'max:1024'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['required_with:faqs', 'string', 'max:500'],
            'faqs.*.answer' => ['required_with:faqs', 'string', 'max:2000'],
            'status' => ['nullable', 'boolean'],

            // assignment details
            'assignment_type' => ['required', Rule::in(['manual', 'automatic'])],
            'match_type' => ['required_if:assignment_type,automatic', Rule::in(['all', 'any'])],
            'rules' => ['required_if:assignment_type,automatic', 'array'],
            'rules.*.field' => ['required_if:assignment_type,automatic'],
            'rules.*.operator' => ['required_if:assignment_type,automatic'],
            'rules.*.value' => ['required_if:assignment_type,automatic'],

            // manual selections
            'courses' => ['nullable', 'array'],
            'courses.*' => ['integer', 'exists:courses,id'],

            'prerequisites' => ['nullable', 'array'],
            'prerequisites.*' => ['integer', 'exists:paths,id'],

            'next_steps' => ['nullable', 'array'],
            'next_steps.*' => ['integer', 'exists:paths,id'],
            'corequisites' => ['nullable', 'array'],
            'corequisites.*' => ['integer', 'exists:paths,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }

        $valid = $validator->validated();

        // Clean and format meta_keywords
        $metaKeywords = null;
        if (isset($valid['meta_keywords']) && $valid['meta_keywords']) {
            $keywords = array_filter(array_map('trim', explode(',', $valid['meta_keywords'])));
            $metaKeywords = implode(', ', $keywords);
        }
        
        $path = Path::create([
            'title' => $valid['title'],
            'english_title' => $valid['english_title'],
            'short_description' => $valid['short_description'] ?? null,
            'description' => $valid['description'] ?? null,
            'meta_keywords' => $metaKeywords,
            'icon' => $valid['icon'] ?? null,
            'poster' => $valid['poster'] ?? null,
            'trailer' => $valid['trailer'] ?? null,
            'faqs' => $valid['faqs'] ?? null,
            'status' => array_key_exists('status', $valid) ? (bool) $valid['status'] : true,
        ]);

        // Manual associations
        if (($valid['assignment_type'] ?? null) === 'manual') {
            if (!empty($valid['courses'])) {
                $path->courses()->sync($valid['courses']);
            }
        }

        // Prerequisites, corequisites & next steps
        if (!empty($valid['prerequisites'])) {
            $path->prerequisites()->syncWithPivotValues($valid['prerequisites'], ['type' => 'prerequisite']);
        }

        if (!empty($valid['next_steps'])) {
            $path->nextSteps()->syncWithPivotValues($valid['next_steps'], ['type' => 'next']);
        }

        if (!empty($valid['corequisites'])) {
            $path->corequisites()->syncWithPivotValues($valid['corequisites'], ['type' => 'corequisite']);
        }

        // Automatic assignment
        if (($valid['assignment_type'] ?? null) === 'automatic' && !empty($valid['rules'])) {
            foreach ($valid['rules'] as $rule) {
                $path->automationRules()->create([
                    'target' => 'course',
                    'match_type' => $valid['match_type'],
                    'field' => $rule['field'],
                    'operator' => $rule['operator'],
                    'value' => $rule['value'],
                ]);
            }

            $this->syncPathAssignments($path);
        }

        return response()->json([
            'message' => 'Success, path created successfully.',
            'path' => [
                'id' => $path->id,
                'title' => $path->title,
                'english_title' => $path->english_title,
                'slug' => $path->slug,
            ],
        ], 200);
    }

    /**
     * Initialize poster upload (similar to course uploadPoster)
     */
    public function uploadPoster(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path_id' => ['required', 'exists:paths,id'],
            'filename' => ['required', 'string'],
            'mime' => ['required', 'string'],
            'size' => ['required', 'integer', 'min:1'],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }
        $valid = $validator->validated();
        $path = Path::findOrFail($request->path_id);
        $disk = 'static';
        $folder = "poster/path/" . date('Y/m/d');
        $ext = pathinfo($valid['filename'], PATHINFO_EXTENSION);
        $generated = Str::uuid()->toString();
        $filePath = "{$folder}/{$generated}.{$ext}";

        $claims = [
            'sub' => 'upload',
            'type' => 'poster',
            'disk' => $disk,
            'path' => $filePath,
            'mime' => $valid['mime'],
            'size' => (int) $valid['size'],
            'pathId' => $path->id,
            'userId' => optional(auth('api')->user())->id,
        ];

        $tokenData = \App\Services\UploadTokenService::generate($claims);

        return response()->json([
            'message' => 'Upload initialized. Use worker to upload the file.',
            'uploadPath' => $filePath,
            'uploadToken' => $tokenData['token'],
            'workerUploadUrl' => rtrim(config('upload.worker_base_url'), '/') . '/api/upload/attachment',
            'expiresAt' => $tokenData['expires_at'],
        ], 200);
    }

    /**
     * Direct upload for icon file (like category icon)
     */
    public function uploadIcon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path_id' => ['required', 'exists:paths,id'],
            'filename' => ['required', 'string'],
            'mime' => ['required', 'string'],
            'size' => ['required', 'integer', 'min:1'],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }
        $valid = $validator->validated();
        $path = Path::findOrFail($request->path_id);
        $disk = 'static';
        $folder = "images/icon/path/" . date('Y/m/d');
        $ext = pathinfo($valid['filename'], PATHINFO_EXTENSION);
        $generated = Str::uuid()->toString();
        $filePath = "{$folder}/{$generated}.{$ext}";

        $claims = [
            'sub' => 'upload',
            'type' => 'attachment',
            'disk' => $disk,
            'path' => $filePath,
            'mime' => $valid['mime'],
            'size' => (int) $valid['size'],
            'pathId' => $path->id,
            'userId' => optional(auth('api')->user())->id,
        ];

        $tokenData = \App\Services\UploadTokenService::generate($claims);

        return response()->json([
            'message' => 'Upload initialized. Use worker to upload the file.',
            'uploadPath' => $filePath,
            'uploadToken' => $tokenData['token'],
            'workerUploadUrl' => rtrim(config('upload.worker_base_url'), '/') . '/api/upload/attachment',
            'expiresAt' => $tokenData['expires_at'],
        ], 200);
    }

    protected function removeUrl($url)
    {
        foreach (config('filesystems.disks') as $disk => $config) {
            if (!isset($config['url'])) continue;
            $baseUrl = rtrim($config['url'], '/');
            if (str_starts_with($url, $baseUrl)) {
                $relativePath = ltrim(str_replace($baseUrl, '', $url), '/');
                if (Storage::disk($disk)->exists($relativePath)) {
                    Storage::disk($disk)->delete($relativePath);
                }
                break;
            }
        }
    }

    /**
     * Direct upload for trailer file and set path->trailer
     */
    public function uploadTrailer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path_id' => ['required', 'exists:paths,id'],
            'filename' => ['required', 'string'],
            'mime' => ['required', 'string'],
            'size' => ['required', 'integer', 'min:1'],
            'process' => ['nullable', 'boolean'],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }
        $valid = $validator->validated();
        $path = Path::findOrFail($request->path_id);
        $disk = 'static';
        $folder = "raw/path/{$path->slug}/trailer";
        $ext = pathinfo($valid['filename'], PATHINFO_EXTENSION);
        $generated = Str::uuid()->toString();
        $filePath = "{$folder}/{$generated}.{$ext}";

        $claims = [
            'sub' => 'upload',
            'type' => 'video',
            'disk' => $disk,
            'path' => $filePath,
            'mime' => $valid['mime'],
            'size' => (int) $valid['size'],
            'process' => (bool)($valid['process'] ?? false),
            'pathId' => $path->id,
            'userId' => optional(auth('api')->user())->id,
        ];

        $tokenData = \App\Services\UploadTokenService::generate($claims);

        return response()->json([
            'message' => 'Upload initialized. Use worker to upload the file.',
            'uploadPath' => $filePath,
            'uploadToken' => $tokenData['token'],
            'workerUploadUrl' => rtrim(config('upload.worker_base_url'), '/') . '/api/upload/video',
            'expiresAt' => $tokenData['expires_at'],
        ], 200);
    }

    protected function syncPathAssignments(Path $path)
    {
        if (!$path->relationLoaded('automationRules')) {
            $path->load('automationRules');
        }

        $rules = $path->automationRules->where('target', 'course');
        if ($rules->isEmpty()) return;

        $matchType = $rules->first()->match_type ?? 'any';
        $query = Course::query()->with(['status:id,title,english_title', 'level:id,title,english_title']);
        $phpFilteredRules = [];

        // Separate rules by field type
        $sqlRules = $rules->where('field', '!=', 'totalTime');
        $phpRules = $rules->where('field', 'totalTime');

        // Extract relation rules (status/level) to handle via whereHas
        $relationRules = $sqlRules->filter(function ($rule) {
            return in_array($rule->field, [
                'status', 'status_title', 'status_english_title',
                'level', 'level_title', 'level_english_title',
            ], true);
        });
        // Remaining (basic column) SQL rules
        $basicSqlRules = $sqlRules->reject(function ($rule) use ($relationRules) {
            return $relationRules->contains($rule);
        });

        // Apply basic (column) SQL rules
        if ($basicSqlRules->isNotEmpty()) {
            if ($matchType === 'all') {
                $query->where(function ($q) use ($basicSqlRules) {
                    foreach ($basicSqlRules as $rule) {
                        $q->where(function ($sub) use ($rule) { $this->applyBasicComparison($sub, $rule->field, $rule); });
                    }
                });
            } else {
                $query->where(function ($q) use ($basicSqlRules) {
                    foreach ($basicSqlRules as $rule) {
                        $q->orWhere(function ($sub) use ($rule) { $this->applyBasicComparison($sub, $rule->field, $rule); });
                    }
                });
            }
        }

        // Apply relation (status/level) rules against title/english_title (matching by text)
        if ($relationRules->isNotEmpty()) {
            if ($matchType === 'all') {
                foreach ($relationRules as $rule) {
                    $this->applyRelationComparison($query, $rule);
                }
            } else {
                $query->where(function ($q) use ($relationRules) {
                    foreach ($relationRules as $rule) {
                        $this->applyRelationComparison($q, $rule, true /* orGroup */);
                    }
                });
            }
        }

        $courses = $query->get();

        // Apply PHP filtering for totalTime rules
        foreach ($phpRules as $rule) {
            $courses = $this->filterByTotalTime($courses, $rule, $matchType);
        }

        foreach ($courses as $course) {
            if (!$course->paths()->where('paths.id', $path->id)->exists()) {
                $course->paths()->attach($path->id);
            }
        }
    }

    protected function applyBasicComparison($query, $column, PathAutomationRule $rule)
    {
        return match ($rule->operator) {
            'is_equal_to' => $query->where($column, '=', $rule->value),
            'not_equal_to' => $query->where($column, '!=', $rule->value),
            'less_than' => $query->where($column, '<', $rule->value),
            'greater_than' => $query->where($column, '>', $rule->value),
            'contains' => $query->where($column, 'LIKE', "%{$rule->value}%"),
            'not_contains' => $query->where($column, 'NOT LIKE', "%{$rule->value}%"),
            'starts_with' => $query->where($column, 'LIKE', "{$rule->value}%"),
            'ends_with' => $query->where($column, 'LIKE', "%{$rule->value}"),
            default => $query,
        };
    }

    protected function filterByTotalTime($courses, PathAutomationRule $rule, $matchType)
    {
        return $courses->filter(function ($course) use ($rule) {
            $value = $course->totalTime() ?? 0;
            $target = intval($rule->value);
            return match ($rule->operator) {
                'is_equal_to' => $value == $target,
                'not_equal_to' => $value != $target,
                'less_than' => $value < $target,
                'greater_than' => $value > $target,
                default => true,
            };
        })->values();
    }

    /**
     * Apply comparison for relation-based rules (status/level) comparing by title/english_title
     */
    protected function applyRelationComparison($query, PathAutomationRule $rule, $orGroup = false)
    {
        $map = [
            'status' => ['relation' => 'status', 'columns' => ['title', 'english_title']],
            'status_title' => ['relation' => 'status', 'columns' => ['title']],
            'status_english_title' => ['relation' => 'status', 'columns' => ['english_title']],
            'level' => ['relation' => 'level', 'columns' => ['title', 'english_title']],
            'level_title' => ['relation' => 'level', 'columns' => ['title']],
            'level_english_title' => ['relation' => 'level', 'columns' => ['english_title']],
        ];
        if (!isset($map[$rule->field])) return $query;
        $relation = $map[$rule->field]['relation'];
        $columns = $map[$rule->field]['columns'];

        $clause = function ($relQ) use ($columns, $rule) {
            // If multiple columns requested, match any (OR)
            $first = true;
            foreach ($columns as $col) {
                $method = $first ? 'where' : 'orWhere';
                $first = false;
                match ($rule->operator) {
                    'is_equal_to' => $relQ->{$method}($col, '=', $rule->value),
                    'not_equal_to' => $relQ->{$method}($col, '!=', $rule->value),
                    'less_than' => $relQ->{$method}($col, '<', $rule->value),
                    'greater_than' => $relQ->{$method}($col, '>', $rule->value),
                    'contains' => $relQ->{$method}($col, 'LIKE', "%{$rule->value}%"),
                    'not_contains' => $relQ->{$method}($col, 'NOT LIKE', "%{$rule->value}%"),
                    'starts_with' => $relQ->{$method}($col, 'LIKE', "{$rule->value}%"),
                    'ends_with' => $relQ->{$method}($col, 'LIKE', "%{$rule->value}"),
                    default => null,
                };
            }
        };

        if ($orGroup) {
            return $query->orWhereHas($relation, $clause);
        }
        return $query->whereHas($relation, $clause);
    }
}


