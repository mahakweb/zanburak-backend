<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Discount;
use App\Models\Path;
use App\Models\Plan;
use App\Models\User;

class CartService
{
    public function __construct(
        protected PriceCalculator $priceCalculator,
        protected DiscountService $discountService
    ) {
    }

    public function getCartItemsResponse($user)
    {
        $this->cleanInvalidCartItems($user);
        $this->recalculate($user);

        $carts = $user->carts()->with(['cartable', 'discount'])->get();
        $coupon = $this->discountService->currentCartCoupon($user);

        $cartItems = $carts->map(function ($cart) use ($user, $coupon) {
            $item = $cart->cartable;
            if (!$item) {
                return null;
            }

            $eligibleCoupon = ($coupon && $this->discountService->isCartItemEligible($coupon, $cart, $user))
                ? $coupon
                : null;
            $pricing = $this->priceCalculator->forCartItem($cart, $eligibleCoupon, $user);

            $base = [
                'id' => $cart->id,
                'allows_installment' => (bool) ($item->allows_installment ?? false),
                'cart_price' => $pricing['final_price'],
                'price' => $pricing['original_price'],
                'original_price' => $pricing['original_price'],
                'current_price' => $pricing['final_price'],
                'course_discount_amount' => $pricing['course_discount_amount'],
                'coupon_discount_amount' => $pricing['coupon_discount_amount'],
                'discount_amount' => $pricing['total_discount'],
                'discount_percentage' => $pricing['discount_percentage'],
                'has_discount' => $pricing['has_discount'],
                'direct_discount' => $pricing['direct_discount'],
                'coupon' => $pricing['coupon'],
            ];

            if ($cart->cartable_type === Course::class) {
                return array_merge($base, [
                    'type' => 'course',
                    'course' => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'english_title' => $item->english_title,
                        'short_description' => $item->short_description,
                        'slug' => $item->slug,
                        'poster' => $item->poster,
                        'price' => $item->price,
                        'original_price' => $pricing['original_price'],
                        'current_price' => $pricing['final_price'],
                        'has_discount' => $pricing['has_direct_discount'],
                        'categories' => $item->category->pluck('id')->toArray(),
                        'teacher' => [
                            'id' => $item->teacher->id,
                            'first_name' => $item->teacher->first_name,
                            'last_name' => $item->teacher->last_name,
                            'username' => $item->teacher->username,
                            'profile_pic' => $item->teacher->profile_pic ?? null,
                        ],
                    ],
                ]);
            }

            if ($cart->cartable_type === Path::class) {
                $availableCourses = $this->priceCalculator->availablePathCourses($item, $user);

                return array_merge($base, [
                    'type' => 'path',
                    'path' => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'english_title' => $item->english_title,
                        'slug' => $item->slug,
                        'icon' => $item->icon,
                        'poster' => $item->poster,
                        'short_description' => $item->short_description,
                        'courses' => $availableCourses->map(function ($course) {
                            return $this->priceCalculator->decorateCourseArray([
                                'id' => $course->id,
                                'title' => $course->title,
                                'english_title' => $course->english_title,
                                'short_description' => $course->short_description,
                                'slug' => $course->slug,
                                'poster' => $course->poster,
                                'type' => $course->type,
                                'price' => $course->price,
                            ], $course);
                        })->values(),
                    ],
                    'path_discount_percent' => $this->priceCalculator->discountPercentForPath,
                ]);
            }

            if ($cart->cartable_type === Plan::class) {
                return array_merge($base, [
                    'type' => 'vip',
                    'vip' => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'english_title' => $item->english_title,
                        'price' => $item->price,
                        'description' => $item->description,
                        'period_time' => $item->period_time,
                        'icon' => $item->icon,
                    ],
                ]);
            }

            return null;
        })->filter()->values();

        $totals = $this->summarize($cartItems, $coupon);

        return array_merge($totals, [
            'items' => $cartItems,
            'has_installment_eligible_items' => $cartItems->contains(fn ($item) => (bool) ($item['allows_installment'] ?? false)),
        ]);
    }

    public function recalculate(User $user, ?Discount $coupon = null): void
    {
        $coupon = $coupon ?? $this->discountService->currentCartCoupon($user);

        if ($coupon) {
            try {
                $this->discountService->validateForCart($coupon, $user);
                $this->discountService->persistCouponOnCart($user, $coupon);
                return;
            } catch (\Throwable $e) {
                $this->discountService->persistWithoutCoupon($user);
                return;
            }
        }

        $this->discountService->persistWithoutCoupon($user);
    }

    public function calculateOriginalPrice($cart)
    {
        $item = $cart->cartable;
        $user = $cart->user;

        if ($cart->cartable_type === Course::class) {
            return (int) $item->price;
        }

        if ($cart->cartable_type === Plan::class) {
            return (int) $item->price;
        }

        if ($cart->cartable_type === Path::class) {
            return $this->priceCalculator->pathOriginalPrice($item, $user);
        }

        return (int) ($item->price ?? 0);
    }

    protected function summarize($cartItems, ?Discount $coupon): array
    {
        $original = (int) $cartItems->sum('original_price');
        $courseDiscount = (int) $cartItems->sum('course_discount_amount');
        $couponDiscount = (int) $cartItems->sum('coupon_discount_amount');
        $totalDiscount = (int) $cartItems->sum('discount_amount');
        $final = (int) $cartItems->sum('current_price');

        return [
            'total_price' => $original,
            'total_original' => $original,
            'total_course_discount' => $courseDiscount,
            'total_coupon_discount' => $couponDiscount,
            'total_discount' => $totalDiscount,
            'final_price' => $final,
            'discount_code' => $coupon?->code,
            'applied_coupon' => $coupon ? [
                'code' => $coupon->code,
                'title' => $coupon->title,
                'type' => $coupon->type,
                'value' => $coupon->value,
                'stackable' => (bool) $coupon->stackable,
            ] : null,
        ];
    }

    protected function cleanInvalidCartItems(User $user): void
    {
        $carts = $user->carts()->with('cartable')->get();
        $invalid = $carts->filter(fn ($cart) => is_null($cart->cartable));
        if ($invalid->isNotEmpty()) {
            $invalid->each->delete();
        }
    }
}
