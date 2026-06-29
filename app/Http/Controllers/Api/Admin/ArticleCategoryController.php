<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ArticleCategoryController extends Controller
{
    public function index()
    {
        $categories = ArticleCategory::ordered()->get();

        return response()->json(['message' => 'Success', 'categories' => $categories]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'english_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'parent_id' => 'nullable|exists:article_categories,id',
            'order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $category = ArticleCategory::create($request->only([
            'title', 'english_title', 'description', 'icon', 'parent_id', 'order', 'status',
        ]));

        return response()->json(['message' => 'Category created', 'category' => $category], 201);
    }

    public function update(Request $request, ArticleCategory $category)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'english_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'parent_id' => 'nullable|exists:article_categories,id',
            'order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $category->update($request->only([
            'title', 'english_title', 'description', 'icon', 'parent_id', 'order', 'status',
        ]));

        return response()->json(['message' => 'Category updated', 'category' => $category]);
    }

    public function delete(ArticleCategory $category)
    {
        if ($category->articles()->exists()) {
            return response()->json(['message' => 'Category has articles and cannot be deleted'], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted']);
    }
}
