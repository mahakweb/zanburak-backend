<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Course;
use App\Models\Discount;
use App\Models\Path;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PriceCalculator
{
    public int $discountPercentForPath = 40;

    protected ?Collection $automaticDiscounts = null;

    /**
     * Pure amount calculation from an original/base price.
     * Coupons and course discounts are always computed against original_price.
     */
    public function amountForDiscount(Discount $discount, int $originalPrice): int
    {
        return $this->amountForType(
            (string) $discount->type,
            $discount->value,
            $originalPrice,
            $discount->max_discount_amount !== null ? (int) $discount->max_discount_amount : null
        );
    }

    public function amountForType(string $type, $value, int $originalPrice, ?int $maxDiscountAmount = null): int
    {
        $originalPrice = max(0, $originalPrice);

        $amount = match ($type) {
            'percent' => (int) floor($originalPrice * ((float) $value / 100)),
            'fixed' => (int) $value,
            'free' => $originalPrice,
            default => 0,
        };

        if ($type === 'percent' && $maxDiscountAmount !== null) {
            $amount = min($amount, max(0, $maxDiscountAmount));
        }

        return max(0, min($amount, $originalPrice));
    }

    /**
     * Combine a direct/internal discount with a coupon.
     *
     * Both amounts must already be calculated from the original price.
     * stackable=true  → apply both (capped at original).
     * stackable=false → apply the larger of the two to avoid double-discounting.
     */
    public function combine(int $originalPrice, int $directDiscount, int $couponDiscount, bool $stackable): array
    {
        $originalPrice = max(0, $originalPrice);
        $directDiscount = max(0, min($directDiscount, $originalPrice));
        $couponDiscount = max(0, min($couponDiscount, $originalPrice));

        if ($stackable) {
            $appliedDirect = $directDiscount;
            $appliedCoupon = $couponDiscount;
        } elseif ($couponDiscount > 0 && $couponDiscount >= $directDiscount) {
            $appliedDirect = 0;
            $appliedCoupon = $couponDiscount;
        } else {
            $appliedDirect = $directDiscount;
            $appliedCoupon = 0;
        }

        $totalDiscount = min($originalPrice, $appliedDirect + $appliedCoupon);
        $finalPrice = max(0, $originalPrice - $totalDiscount);
        $percentage = $originalPrice > 0
            ? (int) round(($totalDiscount / $originalPrice) * 100)
            : 0;

        return [
            'original_price' => $originalPrice,
            'course_discount_amount' => $appliedDirect,
            'coupon_discount_amount' => $appliedCoupon,
            'total_discount' => $totalDiscount,
            'final_price' => $finalPrice,
            'discount_percentage' => $percentage,
            'has_discount' => $totalDiscount > 0,
            'has_direct_discount' => $appliedDirect > 0,
            'stackable' => $stackable,
        ];
    }

    public function emptyPricing(int $originalPrice = 0): array
    {
        $originalPrice = max(0, $originalPrice);

        return [
            'original_price' => $originalPrice,
            'current_price' => $originalPrice,
            'course_discount_amount' => 0,
            'coupon_discount_amount' => 0,
            'total_discount' => 0,
            'final_price' => $originalPrice,
            'discount_percentage' => 0,
            'has_discount' => false,
            'has_direct_discount' => false,
            'direct_discount' => null,
            'coupon' => null,
            'stackable' => false,
        ];
    }

    public function forCourse(Course $course, ?Discount $coupon = null): array
    {
        $original = (int) $course->price;
        if ($original <= 0 || $course->type === 'free') {
            return $this->emptyPricing($original);
        }

        $automatic = $this->bestAutomaticDiscountForCourse($course);
        $directAmount = $automatic ? $this->amountForDiscount($automatic, $original) : 0;
        $couponAmount = ($coupon && $this->couponAppliesToCourse($coupon, $course))
            ? $this->amountForDiscount($coupon, $original)
            : 0;
        $stackable = (bool) ($coupon?->stackable ?? false);

        $combined = $this->combine($original, $directAmount, $couponAmount, $stackable);

        return $this->decorate($combined, $automatic, $coupon);
    }

    public function forPlan(Plan $plan, ?Discount $coupon = null): array
    {
        $original = (int) $plan->price;
        $couponAmount = $coupon ? $this->amountForDiscount($coupon, $original) : 0;
        $stackable = (bool) ($coupon?->stackable ?? false);
        $combined = $this->combine($original, 0, $couponAmount, $stackable);

        return $this->decorate($combined, null, $coupon);
    }

    public function forPath(Path $path, User $user, ?Discount $coupon = null): array
    {
        $original = $this->pathOriginalPrice($path, $user);
        $internal = $this->pathInternalDiscount($original);
        $couponAmount = $coupon ? $this->amountForDiscount($coupon, $original) : 0;
        $stackable = (bool) ($coupon?->stackable ?? false);
        $combined = $this->combine($original, $internal, $couponAmount, $stackable);

        $combined['path_discount_percent'] = $this->discountPercentForPath;

        return $this->decorate($combined, null, $coupon);
    }

    public function forCartItem(Cart $cart, ?Discount $coupon, User $user): array
    {
        $item = $cart->cartable;
        if (!$item) {
            return $this->emptyPricing();
        }

        if ($cart->cartable_type === Course::class) {
            $pricing = $this->forCourse($item, $this->couponEligibleForItem($coupon, $cart, $user) ? $coupon : null);
        } elseif ($cart->cartable_type === Path::class) {
            $pricing = $this->forPath($item, $user, $this->couponEligibleForItem($coupon, $cart, $user) ? $coupon : null);
        } elseif ($cart->cartable_type === Plan::class) {
            $pricing = $this->forPlan($item, $this->couponEligibleForItem($coupon, $cart, $user) ? $coupon : null);
        } else {
            $pricing = $this->emptyPricing((int) ($item->price ?? 0));
        }

        return $pricing;
    }

    public function decorateCourseArray(array $payload, Course $course): array
    {
        $pricing = $this->forCourse($course);

        $payload['price'] = $pricing['original_price'];
        $payload['original_price'] = $pricing['original_price'];
        $payload['current_price'] = $pricing['final_price'];
        $payload['discount_amount'] = $pricing['course_discount_amount'];
        $payload['discount_percentage'] = $pricing['discount_percentage'];
        $payload['has_discount'] = $pricing['has_direct_discount'];
        $payload['direct_discount'] = $pricing['direct_discount'];

        return $payload;
    }

    public function attachToCourseModel(Course $course): Course
    {
        $pricing = $this->forCourse($course);
        $course->setAttribute('original_price', $pricing['original_price']);
        $course->setAttribute('current_price', $pricing['final_price']);
        $course->setAttribute('discount_amount', $pricing['course_discount_amount']);
        $course->setAttribute('discount_percentage', $pricing['discount_percentage']);
        $course->setAttribute('has_discount', $pricing['has_direct_discount']);
        $course->setAttribute('direct_discount', $pricing['direct_discount']);

        return $course;
    }

    public function publicPromotionPayload(Discount $discount): array
    {
        return [
            'id' => $discount->id,
            'code' => $discount->code,
            'title' => $discount->title,
            'description' => $discount->description,
            'type' => $discount->type,
            'value' => $discount->value,
            'max_discount_amount' => $discount->max_discount_amount,
            'banner_title' => $discount->banner_title,
            'banner_description' => $discount->banner_description,
            'banner_icon' => $discount->banner_icon,
            'cta_text' => $discount->cta_text ?: 'مشاهده جزئیات',
            'destination_type' => $discount->destination_type ?: 'promotion',
            'destination_url' => $this->resolveDestinationUrl($discount),
            'starts_at' => optional($discount->starts_at)?->toIso8601String(),
            'ends_at' => optional($discount->ends_at)?->toIso8601String(),
            'priority' => (int) $discount->priority,
            'apply_automatically' => (bool) $discount->apply_automatically,
            'stackable' => (bool) $discount->stackable,
            'server_now' => now()->toIso8601String(),
        ];
    }

    public function activePublicPromotions(): Collection
    {
        $ttl = 30;

        return Cache::remember('discounts.public_promotions', $ttl, function () {
            return $this->activeDiscountQuery()
                ->where('is_public', true)
                ->orderByDesc('priority')
                ->orderBy('ends_at')
                ->get();
        });
    }

    public function forgetPromotionCache(): void
    {
        Cache::forget('discounts.public_promotions');
        $this->automaticDiscounts = null;
    }

    public function pathOriginalPrice(Path $path, User $user): int
    {
        return (int) $this->availablePathCourses($path, $user)->sum('price');
    }

    public function pathInternalDiscount(int $originalPrice): int
    {
        return (int) round($originalPrice * ($this->discountPercentForPath / 100));
    }

    public function availablePathCourses(Path $path, User $user): Collection
    {
        $courseIdsInCart = $user->carts()
            ->where('cartable_type', Course::class)
            ->pluck('cartable_id')
            ->all();
        $userCourseIds = $user->courses()->pluck('courses.id')->all();

        $courses = $path->relationLoaded('courses') ? $path->courses : $path->courses()->get();

        return $courses->where('publish', true)->filter(function ($course) use ($userCourseIds, $courseIdsInCart) {
            return $course->type !== 'free'
                && (int) $course->price > 0
                && !in_array($course->id, $userCourseIds, true)
                && !in_array($course->id, $courseIdsInCart, true);
        })->values();
    }

    public function automaticDiscounts(): Collection
    {
        if ($this->automaticDiscounts !== null) {
            return $this->automaticDiscounts;
        }

        $this->automaticDiscounts = $this->activeDiscountQuery()
            ->where('apply_automatically', true)
            ->with(['eligibilities', 'conditions'])
            ->withCount('usages')
            ->get();

        return $this->automaticDiscounts;
    }

    public function discountedPublishedCourseCount(): int
    {
        return Course::query()
            ->where('publish', 1)
            ->notArchived()
            ->where('price', '>', 0)
            ->with('category')
            ->get()
            ->filter(fn (Course $course) => (bool) $this->forCourse($course)['has_direct_discount'])
            ->count();
    }

    public function bestAutomaticDiscountForCourse(Course $course): ?Discount
    {
        $best = null;
        $bestAmount = -1;
        $original = (int) $course->price;

        foreach ($this->automaticDiscounts() as $discount) {
            if (!$this->couponAppliesToCourse($discount, $course)) {
                continue;
            }
            $used = (int) ($discount->usages_count ?? $discount->usages()->count());
            if ($discount->usage_limit !== null && $used >= $discount->usage_limit) {
                continue;
            }

            $amount = $this->amountForDiscount($discount, $original);
            if ($amount > $bestAmount) {
                $bestAmount = $amount;
                $best = $discount;
            }
        }

        return $best;
    }

    public function couponAppliesToCourse(Discount $discount, Course $course): bool
    {
        $eligibilities = $discount->relationLoaded('eligibilities')
            ? $discount->eligibilities
            : $discount->eligibilities()->get();

        $service = app(DiscountService::class);

        return $service->matchesCourse($discount, $course, $eligibilities);
    }

    protected function couponEligibleForItem(?Discount $coupon, Cart $cart, User $user): bool
    {
        if (!$coupon) {
            return false;
        }

        return app(DiscountService::class)->isCartItemEligible($coupon, $cart, $user);
    }

    protected function decorate(array $combined, ?Discount $automatic, ?Discount $coupon): array
    {
        $combined['current_price'] = $combined['final_price'];
        $combined['direct_discount'] = $automatic ? $this->publicDiscountSummary($automatic) : null;
        $combined['coupon'] = $coupon ? [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'stackable' => (bool) $coupon->stackable,
        ] : null;

        return $combined;
    }

    protected function publicDiscountSummary(Discount $discount): array
    {
        return [
            'id' => $discount->id,
            'code' => $discount->is_public ? $discount->code : null,
            'title' => $discount->title,
            'type' => $discount->type,
            'value' => $discount->value,
            'ends_at' => optional($discount->ends_at)?->toIso8601String(),
        ];
    }

    protected function resolveDestinationUrl(Discount $discount): string
    {
        $type = $discount->destination_type ?: 'promotion';
        $custom = trim((string) $discount->destination_url);

        return match ($type) {
            'custom' => $custom !== '' ? $custom : '/promotions/' . $discount->code,
            'courses' => $custom !== '' ? $custom : '/courses',
            'category' => $custom !== '' ? $custom : '/courses',
            default => '/promotions/' . $discount->code,
        };
    }

    protected function activeDiscountQuery()
    {
        $now = now();

        return Discount::query()
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }
}
