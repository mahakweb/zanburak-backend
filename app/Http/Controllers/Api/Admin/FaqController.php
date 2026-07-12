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
                        'order' => $faq->order,
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
        $perPage = (int) $request->input('perPage', 50);
        $search = $request->input('search', '');
        $status = $request->input('status');

        $query = FaqCategory::with(['faqs' => function ($q) {
            $q->ordered();
        }])->withCount([
            'faqs as faqs_count',
            'faqs as active_faqs_count' => fn ($q) => $q->where('status', true),
            'faqs as inactive_faqs_count' => fn ($q) => $q->where('status', false),
        ])->ordered();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('english_title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('status', filter_var($status, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));
        }

        $categories = $query->paginate($perPage);

        $data = $categories->getCollection()->map(function ($category) {
            return [
                'id' => $category->id,
                'title' => $category->title,
                'english_title' => $category->english_title,
                'slug' => $category->slug,
                'icon' => $category->icon,
                'order' => $category->order,
                'status' => $category->status,
                'faqs_count' => $category->faqs_count,
                'active_faqs_count' => $category->active_faqs_count,
                'inactive_faqs_count' => $category->inactive_faqs_count,
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

        $totalFaqs = (int) Faq::count();
        $totalCategories = FaqCategory::count();

        return response()->json([
            'message' => 'Success',
            'categories' => $data,
            'pagination' => [
                'total' => $categories->total(),
                'per_page' => $categories->perPage(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
            ],
            'stats' => [
                'total_categories' => $totalCategories,
                'active_categories' => FaqCategory::where('status', true)->count(),
                'inactive_categories' => FaqCategory::where('status', false)->count(),
                'empty_categories' => FaqCategory::withCount('faqs')->get()->where('faqs_count', 0)->count(),
                'total_faqs' => $totalFaqs,
                'active_faqs' => Faq::where('status', true)->count(),
                'inactive_faqs' => Faq::where('status', false)->count(),
                'avg_faqs_per_category' => $totalCategories > 0
                    ? round($totalFaqs / $totalCategories, 1)
                    : 0,
            ],
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
    public function deleteCategory(Request $request, FaqCategory $category)
    {
        $faqsCount = $category->faqs()->count();

        if ($faqsCount > 0) {
            if ($request->filled('transfer_to')) {
                $validator = Validator::make($request->all(), [
                    'transfer_to' => 'required|integer|exists:faq_categories,id|not_in:'.$category->id,
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'message' => 'برای حذف این دسته، ابتدا سوالات را به دسته دیگری منتقل کنید.',
                        'errors' => $validator->errors(),
                    ], 422);
                }

                $target = FaqCategory::findOrFail($request->integer('transfer_to'));

                \Illuminate\Support\Facades\DB::transaction(function () use ($category, $target) {
                    $category->faqs()->update(['category_id' => $target->id]);
                    $category->delete();
                });

                return response()->json([
                    'message' => 'Category deleted successfully',
                    'transferred_faqs' => $faqsCount,
                    'transfer_to' => $target->only(['id', 'title', 'slug']),
                ]);
            }

            $validator = Validator::make($request->all(), [
                'transfers' => 'required|array|min:1',
                'transfers.*.faq_id' => 'required|integer|distinct',
                'transfers.*.category_id' => 'required|integer|exists:faq_categories,id|not_in:'.$category->id,
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'برای حذف این دسته، مقصد همه سوالات را مشخص کنید.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $categoryFaqIds = $category->faqs()->pluck('id')->sort()->values();
            $transferFaqIds = collect($request->input('transfers'))->pluck('faq_id')->sort()->values();

            if ($categoryFaqIds->count() !== $transferFaqIds->count()
                || $categoryFaqIds->diff($transferFaqIds)->isNotEmpty()) {
                return response()->json(['message' => 'باید برای همه سوالات این دسته مقصد انتقال مشخص شود.'], 422);
            }

            $transfers = collect($request->input('transfers'));

            \Illuminate\Support\Facades\DB::transaction(function () use ($category, $transfers) {
                foreach ($transfers as $transfer) {
                    $category->faqs()
                        ->where('id', $transfer['faq_id'])
                        ->update(['category_id' => $transfer['category_id']]);
                }
                $category->delete();
            });

            return response()->json([
                'message' => 'Category deleted successfully',
                'transferred_faqs' => $faqsCount,
            ]);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
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

