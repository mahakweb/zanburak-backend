<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Path;
use App\Models\Plan;

class CartService
{
    protected $discountPercentForPath = 40;

    public function getCartItemsResponse($user)
    {
        $carts = $user->carts()->with('cartable')->get();

        $cartItems = $carts->map(function ($cart) use ($user) {
            $item = $cart->cartable;

            if ($cart->cartable_type === Course::class) {
                return [
                    'id' => $cart->id,
                    'type' => 'course',
                    'course' => [
                        'id' => $item->id,
                        'title' => $item->title,
                        'english_title' => $item->english_title,
                        'short_description' => $item->short_description,
                        'slug' => $item->slug,
                        'poster' => $item->poster,
                        'price' => $item->price,
                        'categories' => $item->category->pluck('id')->toArray(),
                        'teacher' => [
                            'id' => $item->teacher->id,
                            'first_name' => $item->teacher->first_name,
                            'last_name' => $item->teacher->last_name,
                            'username' => $item->teacher->username,
                            'profile_pic' => $item->teacher->profile_pic ?? null,
                        ]
                    ],
                    'cart_price' => $cart->price,
                    'price' => $item->price,
                    'discount_amount' => $cart->discount_amount
                ];
            }

            if ($cart->cartable_type === Path::class) {
                $courseIdsInCart = $user->carts->where('cartable_type', Course::class)->pluck('cartable_id')->toArray();
                $userCourseIds = $user->courses->pluck('id')->toArray();

                $availableCourses = $item->courses->where('publish', true)->filter(function ($course) use ($userCourseIds, $courseIdsInCart) {
                    return $course->type !== 'free' && !in_array($course->id, $userCourseIds) && !in_array($course->id, $courseIdsInCart);
                });

                $totalPrice = $availableCourses->sum('price');
                $finalPrice = $totalPrice - ($totalPrice * $this->discountPercentForPath / 100);

                return [
                    'id' => $cart->id,
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
                            return [
                                'id' => $course->id,
                                'title' => $course->title,
                                'english_title' => $course->english_title,
                                'short_description' => $course->short_description,
                                'slug' => $course->slug,
                                'poster' => $course->poster,
                                'type' => $course->type,
                                'price' => $course->price,
                            ];
                        })->values(),
                    ],
                    'price' => $finalPrice,
                    'cart_price' => $cart->price,
                    'discount_amount' => $cart->discount_amount
                ];
            }

            if ($cart->cartable_type === Plan::class) {
                return [
                    'id' => $cart->id,
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
                    'cart_price' => $cart->price,
                    'price' => $item->price,
                    'discount_amount' => $cart->discount_amount
                ];
            }
        });

        $totalPriceOfCart = $cartItems->sum('price');
        $totalDiscountCart = $cartItems->sum('discount_amount');

        return [
            'items' => $cartItems,
            'total_price' => $totalPriceOfCart,
            'total_discount' => $totalDiscountCart,
            'final_price' => $totalPriceOfCart - $totalDiscountCart,
        ];
    }

    /**
     * محاسبه قیمت اصلی آیتم بر اساس نوع آن
     */
    public function calculateOriginalPrice($cart)
    {
        $item = $cart->cartable;

        if ($cart->cartable_type === \App\Models\Course::class) {
            return $item->price;
        }

        if ($cart->cartable_type === \App\Models\Plan::class) {
            return $item->price;
        }

        if ($cart->cartable_type === \App\Models\Path::class) {
            // برای path باید مجموع قیمت دوره‌های موجود را محاسبه کنیم
            $user = $cart->user;
            $courseIdsInCart = $user->carts->where('cartable_type', \App\Models\Course::class)->pluck('cartable_id')->toArray();
            $userCourseIds = $user->courses->pluck('id')->toArray();

            $availableCourses = $item->courses->where('publish', true)->filter(function ($course) use ($userCourseIds, $courseIdsInCart) {
                return $course->type !== 'free' && !in_array($course->id, $userCourseIds) && !in_array($course->id, $courseIdsInCart);
            });

            $totalPrice = $availableCourses->sum('price');
            // اعمال تخفیف 40 درصدی برای path
            return $totalPrice - ($totalPrice * $this->discountPercentForPath / 100);
        }

        return $item->price;
    }
}
