<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Discount;
use App\Services\DiscountService;
use App\Services\PriceCalculator;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct(
        protected PriceCalculator $priceCalculator,
        protected DiscountService $discountService
    ) {
    }

    public function active()
    {
        $now = now()->toIso8601String();
        $promotions = $this->priceCalculator->activePublicPromotions()
            ->map(fn (Discount $discount) => $this->priceCalculator->publicPromotionPayload($discount))
            ->values();

        return response()->json([
            'message' => 'Success',
            'server_now' => $now,
            'promotions' => $promotions,
        ], 200);
    }

    public function show(Request $request, string $code)
    {
        $discount = Discount::query()
            ->whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])
            ->with(['eligibilities', 'conditions'])
            ->first();

        if (!$discount || !$discount->is_public) {
            return response()->json(['error' => 'پروموشن پیدا نشد.'], 404);
        }

        $payload = $this->priceCalculator->publicPromotionPayload($discount);
        $payload['is_expired'] = !$discount->isCurrentlyValid();
        $payload['is_active'] = (bool) $discount->is_active;

        $courses = [];
        if ($discount->isCurrentlyValid()) {
            $courses = $this->eligibleCourses($discount);
        }

        return response()->json([
            'message' => 'Success',
            'server_now' => now()->toIso8601String(),
            'promotion' => $payload,
            'courses' => $courses,
        ], 200);
    }

    protected function eligibleCourses(Discount $discount)
    {
        $query = Course::query()
            ->where('publish', 1)
            ->notArchived()
            ->with(['teacher:id,first_name,last_name,username,profile_pic', 'status:id,title,english_title,slug', 'category']);

        $inclusions = $discount->eligibilities->where('type', 'inclusion');
        $courseIds = $inclusions
            ->filter(fn ($el) => $this->discountService->normalizeTargetType($el->target_type) === 'course')
            ->pluck('target_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();
        $categoryIds = $inclusions
            ->filter(fn ($el) => $this->discountService->normalizeTargetType($el->target_type) === 'category')
            ->pluck('target_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($courseIds !== []) {
            $query->whereIn('id', $courseIds);
        } elseif ($categoryIds !== []) {
            $query->whereHas('category', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            });
        } else {
            $query->where('type', '!=', 'free')->where('price', '>', 0);
        }

        return $query->orderByDesc('id')
            ->limit(24)
            ->get()
            ->filter(fn (Course $course) => $this->discountService->matchesCourse($discount, $course))
            ->map(function (Course $course) {
                $teacher = $course->teacher
                    ? $course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                    : null;

                return $this->priceCalculator->decorateCourseArray([
                    'id' => $course->id,
                    'title' => $course->title,
                    'english_title' => $course->english_title,
                    'slug' => $course->slug,
                    'poster' => $course->poster,
                    'price' => $course->price,
                    'short_description' => $course->short_description,
                    'avgRating' => $course->averageRating(),
                    'total_time' => $course->totalTime(),
                    'likes_count' => $course->likes()->count(),
                    'user_has_liked' => false,
                    'teacher' => $teacher,
                    'status' => $course->status,
                    'allows_installment' => (bool) $course->allows_installment,
                ], $course);
            })
            ->values();
    }
}
