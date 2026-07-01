<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuestionCategoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 15);
        $search = $request->input('search', '');
        $status = $request->input('status', null);
        $parentId = $request->input('parent_id', null);

        $query = QuestionCategory::with(['parent:id,title,english_title', 'children:id,title,english_title,parent_id'])
            ->withCount([
                'questions as questions_count',
                'questions as published_questions_count' => fn ($q) => $q->where('publish', true),
                'questions as draft_questions_count' => fn ($q) => $q->where('publish', false),
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('english_title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($parentId !== null) {
            if ($parentId === 'null' || $parentId === '') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $parentId);
            }
        }

        $categories = $query->orderBy('id', 'desc')->paginate($perPage);

        $categories->getCollection()->transform(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'english_title' => $category->english_title,
                'slug' => $category->slug,
                'parent_id' => $category->parent_id,
                'parent' => $category->parent,
                'children' => $category->children,
                'description' => $category->description,
                'icon' => $category->icon,
                'status' => $category->status,
                'questions_count' => $category->questions_count,
                'published_questions_count' => $category->published_questions_count,
                'draft_questions_count' => $category->draft_questions_count,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ];
        });

        $totalQuestions = (int) Question::count();
        $totalCategories = QuestionCategory::count();

        return response()->json([
            'message' => 'Success',
            'categories' => $categories,
            'stats' => [
                'total_categories' => $totalCategories,
                'active_categories' => QuestionCategory::where('status', true)->count(),
                'inactive_categories' => QuestionCategory::where('status', false)->count(),
                'empty_categories' => QuestionCategory::withCount('questions')->get()->where('questions_count', 0)->count(),
                'total_questions' => $totalQuestions,
                'published_questions' => Question::where('publish', true)->count(),
                'draft_questions' => Question::where('publish', false)->count(),
                'avg_questions_per_category' => $totalCategories > 0
                    ? round($totalQuestions / $totalCategories, 1)
                    : 0,
            ],
        ], 200);
    }

    public function tree(Request $request)
    {
        $categories = QuestionCategory::where('status', 1)
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get();

        $tree = $this->buildTree($categories);

        return response()->json([
            'message' => 'Success',
            'categories' => $tree,
        ], 200);
    }

    public function show(QuestionCategory $category)
    {
        $category->load(['parent', 'children', 'questions']);

        return response()->json([
            'message' => 'Success',
            'category' => $category,
        ], 200);
    }

    public function questions(Request $request, QuestionCategory $category)
    {
        $perPage = (int) $request->input('perPage', 15);

        $query = $category->questions()
            ->with(['user:id,first_name,last_name,username,profile_pic']);

        if ($request->boolean('published_only')) {
            $query->where('publish', true);
        }

        $questions = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        $questions->getCollection()->transform(function ($question) {
            return [
                'id' => $question->id,
                'title' => $question->title,
                'slug' => $question->slug,
                'publish' => $question->publish,
                'is_private' => $question->is_private ?? false,
                'created_at' => $question->created_at,
                'user' => $question->user,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'category' => $category->only(['id', 'title', 'slug', 'english_title']),
            'questions' => $questions,
        ]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'english_title' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:question_categories,id',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = QuestionCategory::create([
            'title' => $request->title,
            'english_title' => $request->english_title,
            'parent_id' => $request->parent_id ?? null,
            'description' => $request->description ?? null,
            'icon' => $request->icon ?? null,
            'status' => $request->status ?? true,
        ]);

        $category->load(['parent', 'children']);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    public function update(Request $request, QuestionCategory $category)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'english_title' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:question_categories,id',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('parent_id') && $request->parent_id) {
            if ($request->parent_id == $category->id) {
                return response()->json(['message' => 'Category cannot be its own parent'], 422);
            }

            if ($this->isDescendant($category->id, $request->parent_id)) {
                return response()->json(['message' => 'Category cannot be a parent of its own descendant'], 422);
            }
        }

        $updateData = [];
        if ($request->has('title')) {
            $updateData['title'] = $request->title;
        }
        if ($request->has('english_title')) {
            $updateData['english_title'] = $request->english_title;
        }
        if ($request->has('parent_id')) {
            $updateData['parent_id'] = $request->parent_id;
        }
        if ($request->has('description')) {
            $updateData['description'] = $request->description;
        }
        if ($request->has('icon')) {
            $updateData['icon'] = $request->icon;
        }
        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }

        $category->update($updateData);
        $category->load(['parent', 'children']);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category,
        ], 200);
    }

    public function delete(Request $request, QuestionCategory $category)
    {
        $questionsCount = $category->questions()->count();

        if ($questionsCount > 0) {
            if ($request->filled('transfer_to')) {
                $validator = Validator::make($request->all(), [
                    'transfer_to' => 'required|integer|exists:question_categories,id|not_in:'.$category->id,
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'message' => 'برای حذف این دسته، ابتدا سوالات را به دسته دیگری منتقل کنید.',
                        'errors' => $validator->errors(),
                    ], 422);
                }

                $target = QuestionCategory::findOrFail($request->integer('transfer_to'));

                DB::transaction(function () use ($category, $target) {
                    $category->questions()->update(['category_id' => $target->id]);
                    $this->reparentChildCategories($category);
                    $category->delete();
                });

                return response()->json([
                    'message' => 'Category deleted successfully',
                    'transferred_questions' => $questionsCount,
                    'transfer_to' => $target->only(['id', 'title', 'slug']),
                ]);
            }

            $validator = Validator::make($request->all(), [
                'transfers' => 'required|array|min:1',
                'transfers.*.question_id' => 'required|integer|distinct',
                'transfers.*.category_id' => 'required|integer|exists:question_categories,id|not_in:'.$category->id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'برای حذف این دسته، مقصد همه سوالات را مشخص کنید.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $categoryQuestionIds = $category->questions()->pluck('id')->sort()->values();
            $transferQuestionIds = collect($request->input('transfers'))->pluck('question_id')->sort()->values();

            if ($categoryQuestionIds->count() !== $transferQuestionIds->count()
                || $categoryQuestionIds->diff($transferQuestionIds)->isNotEmpty()) {
                return response()->json(['message' => 'باید برای همه سوالات این دسته مقصد انتقال مشخص شود.'], 422);
            }

            $transfers = collect($request->input('transfers'));
            $summary = $transfers->groupBy('category_id')->map(fn ($items, $catId) => [
                'category_id' => (int) $catId,
                'count' => $items->count(),
            ])->values();

            DB::transaction(function () use ($category, $transfers) {
                foreach ($transfers as $transfer) {
                    $category->questions()
                        ->where('id', $transfer['question_id'])
                        ->update(['category_id' => $transfer['category_id']]);
                }
                $this->reparentChildCategories($category);
                $category->delete();
            });

            $targetCategories = QuestionCategory::query()
                ->whereIn('id', $summary->pluck('category_id'))
                ->get(['id', 'title', 'slug'])
                ->keyBy('id');

            return response()->json([
                'message' => 'Category deleted successfully',
                'transferred_questions' => $questionsCount,
                'transfer_summary' => $summary->map(function ($row) use ($targetCategories) {
                    $target = $targetCategories->get($row['category_id']);

                    return [
                        'count' => $row['count'],
                        'category' => $target?->only(['id', 'title', 'slug']),
                    ];
                })->values(),
            ]);
        }

        DB::transaction(function () use ($category) {
            $this->reparentChildCategories($category);
            $category->delete();
        });

        return response()->json(['message' => 'Category deleted successfully']);
    }

    private function reparentChildCategories(QuestionCategory $category): void
    {
        foreach ($category->children as $child) {
            $child->parent_id = $category->parent_id;
            $child->save();
        }
    }

    private function buildTree($categories, $parentId = null)
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildTree($categories, $category->id);
                $categoryData = [
                    'id' => $category->id,
                    'title' => $category->title,
                    'english_title' => $category->english_title,
                    'slug' => $category->slug,
                    'parent_id' => $category->parent_id,
                    'description' => $category->description,
                    'icon' => $category->icon,
                    'status' => $category->status,
                ];

                if (count($children) > 0) {
                    $categoryData['children'] = $children;
                }

                $tree[] = $categoryData;
            }
        }

        return $tree;
    }

    private function isDescendant($ancestorId, $categoryId)
    {
        $category = QuestionCategory::find($categoryId);

        if (! $category || ! $category->parent_id) {
            return false;
        }

        if ($category->parent_id == $ancestorId) {
            return true;
        }

        return $this->isDescendant($ancestorId, $category->parent_id);
    }

    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:question_categories,id',
            'action' => 'required|in:activate,deactivate',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = $request->input('action') === 'activate';
        $affected = QuestionCategory::whereIn('id', $request->input('ids'))
            ->where('status', '!=', $status)
            ->update(['status' => $status]);

        return response()->json([
            'message' => 'Bulk action completed',
            'affected' => $affected,
        ]);
    }
}
