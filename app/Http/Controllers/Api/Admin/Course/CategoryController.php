<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\AutomationRule;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
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

            $this->syncCategoryAssignments($category);

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

            $category->course()->detach();

            $this->syncCategoryAssignments($category);


            return response()->json(['message' => "Category updated successfully", 'category' => $category], 200);
        }
    }


    public function syncCategoryAssignments(Category $category)
    {
        if ($category->assignment_type !== 'automatic') {
            return;
        }

        $matchedCourses = $this->getMatchingCourses($category);

        foreach ($matchedCourses as $course) {
            if (!$course->category()->where('categories.id', $category->id)->exists()) {
                $course->category()->attach($category->id);
            }
        }
    }

    protected function getMatchingCourses(Category $category)
    {
        $rules = $category->automationRules;
        $query = Course::query();
        $phpFilteredRules = [];

        if ($category->match_type === 'all') {
            // foreach ($rules as $rule) {
            //     if ($rule->field === 'totalTime') {
            //         $phpFilteredRules[] = $rule;
            //         continue;
            //     }
            //     $this->applyRule($query, $rule);
            // }
            $query->where(function ($q) use ($rules, &$phpFilteredRules) {
                foreach ($rules as $rule) {
                    if ($rule->field === 'totalTime') {
                        $phpFilteredRules[] = $rule;
                        continue;
                    }
                    // برای همه شروط AND
                    $q->where(function ($subQuery) use ($rule) {
                        $this->applyRule($subQuery, $rule);
                    });
                }
            });
        } else {
            $query->where(function ($q) use ($rules, &$phpFilteredRules) {
                foreach ($rules as $rule) {
                    if ($rule->field === 'totalTime') {
                        $phpFilteredRules[] = $rule;
                        continue;
                    }
                    $q->orWhere(function ($subQuery) use ($rule) {
                        $this->applyRule($subQuery, $rule);
                    });
                }
            });
        }

        $courses = $query->get();

        foreach ($phpFilteredRules as $rule) {
            $courses = $this->filterByTotalTime($courses, $rule, $category->match_type);
        }

        return $courses;
    }



    protected function applyRule($query, AutomationRule $rule)
    {
        switch ($rule->field) {
            case 'status':
                return $query->whereHas('status', function ($q) use ($rule) {
                    $this->applyMultiColumnComparison($q, ['title', 'english_title', 'slug'], $rule);
                });

            case 'level':
                return $query->whereHas('level', function ($q) use ($rule) {
                    $this->applyMultiColumnComparison($q, ['title', 'english_title', 'slug'], $rule);
                });

            case 'totalTime':
                return $query;

            default:
                return $this->applyBasicComparison($query, $rule->field, $rule);
        }
    }



    protected function applyMultiColumnComparison($query, array $columns, AutomationRule $rule)
    {
        return $query->where(function ($q) use ($columns, $rule) {
            foreach ($columns as $column) {
                $q->orWhere(function ($sub) use ($column, $rule) {
                    $this->applyBasicComparison($sub, $column, $rule);
                });
            }
        });
    }



    protected function applyBasicComparison($query, $column, AutomationRule $rule)
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


    protected function filterByTotalTime($courses, AutomationRule $rule, $matchType)
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
            $category->icon = Storage::disk($disk)->url($filePath);
            $category->save();
            return response()->json(['message' => "Icon uploaded successfully", 'icon' => $category->icon], 200);
        }
    }


    public function removeIcon($category)
    {
        if ($category->icon) {
            $disk = $this->urlDetails($category->icon)['disk'];
            $path = $this->urlDetails($category->icon)['path'];
            if ($category->icon && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                $category->icon = null;
                $category->save();
            }
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
