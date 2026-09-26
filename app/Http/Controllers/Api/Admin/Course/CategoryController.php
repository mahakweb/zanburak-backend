<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Course\CategoryAssignmentService;
use App\Services\ImageWebpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryAssignmentService $assignmentService,
    ) {}

    public function categories(Request $request)
    {
        $view = $request->input('view', 'list');

        return match ($view) {
            'list' => $this->categoriesList($request),
            'tree' => $this->categoriesTree($request),
            default => $this->categoriesList($request),
        };
    }


    public function categoriesTree(Request $request)
    {
        $status = $request->input('status');
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');

        $query = Category::with([
            'course:id,title,english_title,slug,short_description,description,poster',
            'parent',
            'children',
        ])
            ->whereNull('parent_id')
            ->status($status);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('english_title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $categories = $query->paginate($perPage);

        $data = $categories->map(function ($category) {
            return $this->transformCategory($category);
        });

        return response()->json([
            'message' => 'Success',
            'categories' => $data,
            'pagination' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'prev_page' => $categories->currentPage() > 1 ? $categories->currentPage() - 1 : null,
                'next_page' => $categories->hasMorePages() ? $categories->currentPage() + 1 : null
            ]
        ]);
    }


    protected function transformCategory($category)
    {
        return [
            'id' => $category->id,
            'title' => $category->title,
            'english_title' => $category->english_title,
            'slug' => $category->slug,
            'icon' => $category->icon,
            'status' => $category->status,
            'description' => $category->description,
            'parent_id' => $category->parent_id,
            'parent' => $category->parent,
            'courses_count' => $category->course()->count(),
            'courses' => $category->course,
            'assignment_type' => $category->assignment_type,
            'children' => $category->children->map(function ($child) {
                return $this->transformCategory($child);
            }),
            'created_at' => $category->created_at,
            'updated_at' => $category->updated_at,
        ];
    }


    public function categoriesList(Request $request)
    {
        $status = $request->input('status');
        $order = $request->input('sort');
        $perPage = $request->input('perPage', 10);
        $search = $request->input('search');

        $query = Category::with([
            'course:id,title,english_title,slug,short_description,description,poster',
            'parent',
            // 'children',
        ])
            ->status($status)
            ->order($order);
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('english_title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        $categories = $query->paginate($perPage);

        $data = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'english_title' => $category->english_title,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'status' => $category->status,
                'description' => $category->description,
                'parent_id' => $category->parent_id,
                'parent' => $category->parent,
                'courses_count' => $category->course()->count(),
                'courses' => $category->course,
                'assignment_type' => $category->assignment_type,
                // 'children' => $category->children->map(function ($child) {
                //     return $this->transformCategory($child);
                // }),
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'categories' => $data,
            'pagination' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'prev_page' => $categories->currentPage() > 1 ? $categories->currentPage() - 1 : null,
                'next_page' => $categories->hasMorePages() ? $categories->currentPage() + 1 : null,
            ]
        ]);
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:3', 'max:255', 'unique:categories,title'],
            'english_title' => ['required', 'min:3', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', 'unique:categories,english_title'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['required', 'min:10'],
            'tags' => ['nullable', 'max:3'],
            'status' => ['required', 'in:0,1'],
            'assignment_type' => ['required', 'in:manual,automatic'],
            'match_type' => ['required_if:assignment_type,automatic', 'in:all,any'],
            'rules' => ['required_if:assignment_type,automatic', 'array'],
            'rules.*.field' => ['required_if:assignment_type,automatic'],
            'rules.*.operator' => ['required_if:assignment_type,automatic'],
            'rules.*.value' => ['required_if:assignment_type,automatic'],
        ], [
            'match_type.required_if' => 'نوع انطباق زمانی که روش اختصاص برابر با "خودکار" باشد الزامی است .',
            'rules.required_if' => 'وارد کردن شروط زمانی که روش اختصاص برابر با "خودکار" باشد الزامی است.',
            'rules.*.field.required_if' => 'فیلد (فیلد شرط) زمانی که روش اختصاص برابر با "خودکار" باشد الزامی است .',
            'rules.*.operator.required_if' => ' فیلد (عملگر شرط) زمانی که روش اختصاص برابر با "خودکار" باشد الزامی است.',
            'rules.*.value.required_if' => ' فیلد (مقدار شرط) زمانی که روش اختصاص برابر با "خودکار" باشد الزامی است.',
        ], [
            'rules.*.field' => '(فیلد شرط)',
            'rules.*.operator' => '(عملگر شرط)',
            'rules.*.value' => '(مقدار شرط)',
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();

            $category = Category::create($validData);

            $tags = is_string($request->tags) ? json_decode($request->tags, true) : $request->tags;

            foreach ($tags as $tag) {
                $category->tag($tag);
            }

            if ($request->assignment_type === 'automatic' && is_array($request->rules)) {
                foreach ($request->rules as $rule) {
                    $category->automationRules()->create($rule);
                }
            }

            $this->assignmentService->syncCategory($category->fresh('automationRules'));

            return response()->json(['message' => "Success, category created successfully.", 'category' => $category], 200);
        }
    }

    public function edit(Request $request)
    {
        $slug = $request->slug;
        $category = Category::with(['tags', 'automationRules', 'parent'])->where('slug', $slug)->first();

        if (!$category) {
            return response()->json(['message' => 'Error! category not found'], 404);
        }


        $automationRules = $category->automationRules->map(fn($autrul) => [
            'id' => $autrul->id,
            'field' => $autrul->field,
            'operator' => $autrul->operator,
            'value' => $autrul->value,
        ]);

        $tags = $category->tags->pluck('name');

        $parent = $category->parent ? [
            'id' => $category->parent->id,
            'title' => $category->parent->title,
            'slug' => $category->parent->slug,
            'english_title' => $category->parent->english_title,
            'icon' => $category->parent->icon,
        ] : null;


        $matchedCourses = $category->assignment_type === 'automatic'
            ? $this->assignmentService->getMatchingCourses($category)->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'poster' => $course->poster,
            ])->values()
            : collect();

        $response = [
            'id' => $category->id,
            'title' => $category->title,
            'english_title' => $category->english_title,
            'slug' => $category->slug,
            'status' => $category->status,
            'description' => $category->description,
            'icon' => $category->icon,
            'parent' => $parent,
            'tags' => $tags,
            'rules' => $automationRules,
            'assignment_type' => $category->assignment_type,
            'match_type' => $category->match_type,
            'matched_courses' => $matchedCourses,
            'matched_courses_count' => $matchedCourses->count(),
        ];

        return response()->json([
            'message' => 'Success',
            'category' => $response,
        ]);
    }

    public function update(Request $request)
    {
        $user = auth('api')->user();
        $category = Category::find($request->input('category_id'));
        if (!$category) {
            return response()->json(['message' => 'Error! Category not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'min:3', 'max:255', Rule::unique('categories', 'title')->ignore($category->id)],
            'english_title' => ['required', 'min:3', 'max:255', 'regex:/^[~`!@#$%^&*()_+=[\]\\{}|;":",.\/<>?a-zA-Z0-9- ]+$/', Rule::unique('categories', 'english_title')->ignore($category->id)],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'description' => ['required', 'min:10'],
            'tags' => ['nullable', 'max:3'],
            'status' => ['required', 'in:0,1'],
            'assignment_type' => ['required', 'in:manual,automatic'],
            'match_type' => ['required_if:assignment_type,automatic', 'in:all,any'],
            'rules' => ['required_if:assignment_type,automatic', 'array'],
            'rules.*.field' => ['required_if:assignment_type,automatic'],
            'rules.*.operator' => ['required_if:assignment_type,automatic'],
            'rules.*.value' => ['required_if:assignment_type,automatic'],
        ], [
            'match_type.required_if' => 'نوع انطباق زمانی که روش اختصاص برابر با "خودکار" باشد الزامی است .',
            'rules.required_if' => 'وارد کردن شروط زمانی که روش اختصاص برابر با "خودکار" باشد الزامی است.',
            'rules.*.field.required_if' => 'فیلد (فیلد شرط) زمانی که روش اختصاص برابر با "خودکار" باشد الزامی است .',
            'rules.*.operator.required_if' => ' فیلد (عملگر شرط) زمانی که روش اختصاص برابر با "خودکار" باشد الزامی است.',
            'rules.*.value.required_if' => ' فیلد (مقدار شرط) زمانی که روش اختصاص برابر با "خودکار" باشد الزامی است.',
        ], [
            'rules.*.field' => '(فیلد شرط)',
            'rules.*.operator' => '(عملگر شرط)',
            'rules.*.value' => '(مقدار شرط)',
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            $category->update($validData);
            $category->retag($validData['tags']);
            $category->automationRules()->delete();
            if ($request->assignment_type === 'automatic' && is_array($request->rules)) {
                foreach ($request->rules as $rule) {
                    $category->automationRules()->create($rule);
                }
            }

            if ($category->assignment_type === 'automatic') {
                $this->assignmentService->syncCategory($category->fresh('automationRules'));
            }

            return response()->json(['message' => "Category updated successfully", 'category' => $category], 200);
        }
    }


    public function previewCourses(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'match_type' => ['required', 'in:all,any'],
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.field' => ['required', 'string'],
            'rules.*.operator' => ['required', 'string'],
            'rules.*.value' => ['required'],
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        }

        $validData = $validator->validated();
        $category = new Category([
            'match_type' => $validData['match_type'],
            'assignment_type' => 'automatic',
        ]);

        $courses = $this->assignmentService
            ->getMatchingCourses($category, $validData['match_type'], $validData['rules'])
            ->map(fn ($course) => [
                'id' => $course->id,
                'title' => $course->title,
                'english_title' => $course->english_title,
                'slug' => $course->slug,
                'poster' => $course->poster,
            ])
            ->values();

        return response()->json([
            'message' => 'Success',
            'courses' => $courses,
            'count' => $courses->count(),
        ]);
    }

    public function uploadIcon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:categories,id'],
            'icon' => ['required', 'mimes:jpg,svg,png,jpeg,webp', 'max:5120'],
        ]);
        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $user = auth('api')->user();
            $validData = $validator->validated();
            $category = Category::findOrFail($request->category_id);
            if (!$category) {
                return response()->json(['message' => 'Error! category not found'], 404);
            }
            $this->removeIcon($category);
            $file = $request->file('icon');
            $disk = 'static';
            $folder = "images/icon/category/" . date('Y/m/d');
            $filePath = $file->store($folder, $disk);
            app(ImageWebpService::class)->ensureSibling($disk, $filePath, $file->getRealPath());
            $category->icon = Storage::disk($disk)->url($filePath);
            $category->save();
            return response()->json(['message' => "Icon uploaded successfully", 'icon' => $category->icon], 200);
        }
    }


    public function removeIcon($category)
    {
        if ($category->icon) {
            app(ImageWebpService::class)->safeDeleteStoredMedia($category->icon);
            $category->icon = null;
            $category->save();
        }
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




    public function deleteCategories(Request $request)
    {
        $ids = $request->input('id');

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $categories = Category::whereIn('id', $ids)->get();

        if ($categories->isEmpty()) {
            return response()->json([
                'message' => 'Not found any category for delete',
            ], 404);
        }

        foreach ($categories as $category) {
            $this->deleteCategoryRecursively($category);
        }

        return response()->json([

            'message' => 'Success, category(ies) deleted successfully',
        ], 200);
    }

    protected function deleteCategoryRecursively($category)
    {
        foreach ($category->children as $child) {
            $this->deleteCategoryRecursively($child);
        }
        $this->removeIcon($category);
        $category->delete();
    }
}
