<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FaqController extends Controller
{
    // Get all FAQs with categories for frontend
    public function index()
    {
        $categories = FaqCategory::with(['faqs' => function ($query) {
            $query->active()->ordered();
        }])
            ->active()
            ->ordered()
            ->get();

        $data = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'slug' => $category->slug,
                'category' => $category->title,
                'icon' => $category->icon,
                'questions' => $category->faqs->map(function ($faq) {
                    return [
                        'id' => $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                    ];
                }),
            ];
        });

        return response()->json([
            'message' => 'Success',
            'faqs' => $data,
        ], 200);
    }

    // Admin: Get all categories with FAQs
    public function categories(Request $request)
    {
        $perPage = $request->input('perPage', 10);

        $categories = FaqCategory::with(['faqs' => function ($query) {
            $query->ordered();
        }])
            ->ordered()
            ->paginate($perPage);

        $data = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'english_title' => $category->english_title,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'order' => $category->order,
                'status' => $category->status,
                'faqs_count' => $category->faqs->count(),
                'faqs' => $category->faqs->map(function ($faq) {
                    return [
                        'id' => $faq->id,
                        'question' => $faq->question,
                        'answer' => $faq->answer,
                        'order' => $faq->order,
                        'status' => $faq->status,
                        'category_id' => $faq->category_id,
                    ];
                }),
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
            ]
        ], 200);
    }

    // Admin: Create category
    public function createCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'english_title' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category = FaqCategory::create([
            'title' => $request->title,
            'english_title' => $request->english_title,
            'icon' => $request->icon,
            'order' => $request->order ?? 0,
            'status' => $request->status ?? true,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'category' => $category,
        ], 201);
    }

    // Admin: Update category
    public function updateCategory(Request $request, FaqCategory $category)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'english_title' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category->update([
            'title' => $request->title,
            'english_title' => $request->english_title,
            'icon' => $request->icon,
            'order' => $request->order ?? $category->order,
            'status' => $request->status ?? $category->status,
        ]);

        return response()->json([
            'message' => 'Category updated successfully',
            'category' => $category,
        ], 200);
    }

    // Admin: Delete category
    public function deleteCategory(FaqCategory $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ], 200);
    }

    // Admin: Create FAQ
    public function createFaq(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:faq_categories,id',
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $faq = Faq::create([
            'category_id' => $request->category_id,
            'question' => $request->question,
            'answer' => $request->answer,
            'order' => $request->order ?? 0,
            'status' => $request->status ?? true,
        ]);

        return response()->json([
            'message' => 'FAQ created successfully',
            'faq' => $faq,
        ], 201);
    }

    // Admin: Update FAQ
    public function updateFaq(Request $request, Faq $faq)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:faq_categories,id',
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'order' => 'nullable|integer|min:0',
            'status' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $faq->update([
            'category_id' => $request->category_id,
            'question' => $request->question,
            'answer' => $request->answer,
            'order' => $request->order ?? $faq->order,
            'status' => $request->status ?? $faq->status,
        ]);

        return response()->json([
            'message' => 'FAQ updated successfully',
            'faq' => $faq,
        ], 200);
    }

    // Admin: Delete FAQ
    public function deleteFaq(Faq $faq)
    {
        $faq->delete();

        return response()->json([
            'message' => 'FAQ deleted successfully',
        ], 200);
    }

    // Admin: Reorder FAQs (similar to episodes reorder)
    public function reorderFaqs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'faqs' => 'required|array',
            'faqs.*.id' => 'required|integer|exists:faqs,id',
            'faqs.*.category_id' => 'required|integer|exists:faq_categories,id',
            'faqs.*.order' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->faqs as $faqData) {
            Faq::where('id', $faqData['id'])->update([
                'category_id' => $faqData['category_id'],
                'order' => $faqData['order'],
            ]);
        }

        return response()->json([
            'message' => 'FAQs reordered successfully',
        ], 200);
    }
}

