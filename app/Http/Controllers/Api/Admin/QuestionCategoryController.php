<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QuestionCategoryController extends Controller
{
    // Get all categories with pagination
    public function index(Request $request)
    {
        $perPage = $request->input('perPage', 15);
        $search = $request->input('search', '');
        $status = $request->input('status', null);
        $parentId = $request->input('parent_id', null);

        $query = QuestionCategory::with(['parent:id,title,english_title', 'children:id,title,english_title,parent_id'])
            ->withCount('questions');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('english_title', 'like', "%{$search}%");
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
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
            ];
        });

        return response()->json([
            'message' => 'Success',
            'categories' => $categories,
        ], 200);
    }

    // Get all categories as tree (for dropdowns)
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

    // Get single category
    public function show(QuestionCategory $category)
    {
        $category->load(['parent', 'children', 'questions']);

        return response()->json([
            'message' => 'Success',
            'category' => $category,
        ], 200);
    }

    // Create category
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

    // Update category
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

        // Prevent setting parent to itself or its children
        if ($request->has('parent_id') && $request->parent_id) {
            if ($request->parent_id == $category->id) {
                return response()->json([
                    'message' => 'Category cannot be its own parent',
                ], 422);
            }

            // Check if parent is a child of this category
            $isDescendant = $this->isDescendant($category->id, $request->parent_id);
            if ($isDescendant) {
                return response()->json([
                    'message' => 'Category cannot be a parent of its own descendant',
                ], 422);
            }
        }

        $updateData = [];
        if ($request->has('title')) $updateData['title'] = $request->title;
        if ($request->has('english_title')) $updateData['english_title'] = $request->english_title;
        if ($request->has('parent_id')) $updateData['parent_id'] = $request->parent_id;
        if ($request->has('description')) $updateData['description'] = $request->description;
        if ($request->has('icon')) $updateData['icon'] = $request->icon;
        if ($request->has('status')) $updateData['status'] = $request->status;

        $category->update($updateData);
        $category->load(['parent', 'children']);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category,
        ], 200);
    }

    // Delete category
    public function delete(QuestionCategory $category)
    {
        // Check if category has questions
        if ($category->questions()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with existing questions',
            ], 422);
        }

        // Move children to parent or make them root
        $children = $category->children;
        foreach ($children as $child) {
            $child->parent_id = $category->parent_id;
            $child->save();
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ], 200);
    }

    // Helper method to build tree structure
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

    // Helper method to check if a category is a descendant
    private function isDescendant($ancestorId, $categoryId)
    {
        $category = QuestionCategory::find($categoryId);
        
        if (!$category || !$category->parent_id) {
            return false;
        }

        if ($category->parent_id == $ancestorId) {
            return true;
        }

        return $this->isDescendant($ancestorId, $category->parent_id);
    }
}

