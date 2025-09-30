<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Course;
use App\Models\Discount;
use App\Models\Path;
use App\Models\Plan;
use App\Services\DiscountService;

class CartController extends Controller
{
    public $discountPercentForPath = 40;
    protected $cartService;
    protected $typeMap = [
        'course' => Course::class,
        'path' => Path::class,
        'vip' => Plan::class,
    ];

    protected function resolveCartableType(string $type): string
    {
        $type = strtolower($type);
        if (!isset($this->typeMap[$type])) {
            abort(400, 'نوع آیتم نامعتبر است.');
        }
        return $this->typeMap[$type];
    }


    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    // protected function getCartItemsResponse($user)
    // {
    //     $carts = $user->carts()->with('cartable')->get();

    //     $cartItems = $carts->map(function ($cart) use ($user) {
    //         $item = $cart->cartable;

    //         if ($cart->cartable_type === Course::class) {
    //             return [
    //                 'id' => $cart->id,
    //                 'type' => 'course',
    //                 'course' => [
    //                     'id' => $item->id,
    //                     'title' => $item->title,
    //                     'english_title' => $item->english_title,
    //                     'short_description' => $item->short_description,
    //                     'slug' => $item->slug,
    //                     'poster' => $item->poster,
    //                     'price' => $item->price,
    //                     'teacher' => [
    //                         'id' => $item->teacher->id,
    //                         'first_name' => $item->teacher->first_name,
    //                         'last_name' => $item->teacher->last_name,
    //                         'username' => $item->teacher->username,
    //                         'profile_pic' => $item->teacher->profile_pic ?? null,
    //                     ]
    //                 ],
    //                 'cart_price' => $cart->price,
    //                 'price' => $item->price,
    //                 'discount_amount' => $cart->discount_amount
    //             ];
    //         } elseif ($cart->cartable_type === Path::class) {

    //             $courseIdsInCart = $user->carts->where('cartable_type', 'App\Models\Course')->pluck('cartable_id')->toArray();
    //             $userCourseIds = $user->courses->pluck('id')->toArray();
    //             $availableCourses = $item->courses->where('publish', true)->filter(function ($course) use ($userCourseIds, $courseIdsInCart) {
    //                 return $course->type !== 'free' && !in_array($course->id, $userCourseIds) && !in_array($course->id, $courseIdsInCart);
    //             });
    //             $totalPrice = $availableCourses->sum('price');


    //             $finalPrice = $totalPrice - ($totalPrice * $this->discountPercentForPath / 100);
    //             return [
    //                 'id' => $cart->id,
    //                 'type' => 'path',
    //                 'path' => [
    //                     'id' => $item->id,
    //                     'title' => $item->title,
    //                     'english_title' => $item->english_title,
    //                     'slug' => $item->slug,
    //                     'icon' => $item->icon,
    //                     'poster' => $item->poster,
    //                     'short_description' => $item->short_description,
    //                     'courses' => $availableCourses->map(function ($course) {
    //                         return [
    //                             'id' => $course->id,
    //                             'title' => $course->title,
    //                             'english_title' => $course->english_title,
    //                             'short_description' => $course->short_description,
    //                             'slug' => $course->slug,
    //                             'poster' => $course->poster,
    //                             'type' => $course->type,
    //                             'price' => $course->price,
    //                         ];
    //                     })->values(),
    //                 ],
    //                 'price' => $finalPrice,
    //                 'cart_price' => $cart->price,
    //                 'discount_amount' => $cart->discount_amount
    //             ];
    //         } elseif ($cart->cartable_type === Plan::class) {
    //             return [
    //                 'id' => $cart->id,
    //                 'type' => 'vip',
    //                 'vip' => [
    //                     'id' => $item->id,
    //                     'title' => $item->title,
    //                     'english_title' => $item->english_title,
    //                     'price' => $item->price,
    //                     'description' => $item->description,
    //                     'period_time' => $item->period_time,
    //                     'icon' => $item->icon,
    //                 ],
    //                 'cart_price' => $cart->price,
    //                 'price' => $item->price,
    //                 'discount_amount' => $cart->discount_amount
    //             ];
    //         }
    //     });

    //     $totalPriceOfCart = $cartItems->sum('price');
    //     $totalDiscountCart = $cartItems->sum('discount_amount');

    // return [
    //     'items' => $cartItems,
    //     'total_price' => $totalPriceOfCart,
    //     'total_discount' => $totalDiscountCart,
    // ];
    // }


    public function index(Request $request)
    {
        $user = auth('api')->user();
        
        // پاک کردن کدهای تخفیف موجود و بازگردانی قیمت اصلی
        $carts = $user->carts()->with('cartable')->get();
        foreach ($carts as $cart) {
            $originalPrice = $this->cartService->calculateOriginalPrice($cart);
            $cart->update([
                'discount_id' => null,
                'discount_amount' => 0,
                'price' => $originalPrice
            ]);
        }
        
        $cartResponse = $this->cartService->getCartItemsResponse($user);
        return response()->json([
            'message' => 'Success',
            'cartItems' => $cartResponse['items'],
            'total_price' => $cartResponse['total_price'],
            'total_discount' => $cartResponse['total_discount'],
        ], 200);
    }

    public function add(Request $request)
    {
        $user = auth('api')->user();
        $type = $request->input('type');
        $id = $request->input('item_id');

        $typeClass = $this->resolveCartableType($type);
        $item = $typeClass::findOrFail($id);

        switch ($type) {
            case 'vip':
                if ($user->hasVip()) {
                    return response()->json(['error' => 'شما در حال حاضر اشتراک فعال دارید.'], 422);
                }

                if ($user->carts()->where('cartable_type', $typeClass)->exists()) {
                    return response()->json(['error' => 'شما هم‌اکنون یک اشتراک در سبد خرید دارید.'], 422);
                }

                if (!$item->status) {
                    return response()->json(['error' => 'اشتراک وجود ندارد یا غیرفعال است.'], 422);
                }

                $user->carts()->create([
                    'cartable_type' => get_class($item),
                    'cartable_id' => $item->id,
                    'price' => $item->price,
                ]);
                break;

            case 'course':
                if ($user->courses()->where('courses.id', $item->id)->exists()) {
                    return response()->json(['error' => 'این دوره قبلاً توسط شما خریداری شده است.'], 422);
                }

                if ($item->price <= 0 || $item->type == 'free') {
                    return response()->json(['error' => 'این دوره رایگان است و نیازی به خرید ندارد.'], 422);
                }

                $pathIds = $user->carts()
                    ->where('cartable_type', $this->resolveCartableType('path'))
                    ->pluck('cartable_id');

                $hasPathInCart = Path::whereIn('id', $pathIds)
                    ->whereHas('courses', function ($q) use ($item) {
                        $q->where('courses.id', $item->id);
                    })
                    ->exists();

                if ($hasPathInCart) {
                    return response()->json(['error' => 'این دوره در مسیر انتخابی شما در سبد وجود دارد.'], 422);
                }

                if (!$item->publish) {
                    return response()->json(['error' => 'دوره وجود ندارد یا غیرفعال است.'], 422);
                }

                $user->carts()->create([
                    'cartable_type' => get_class($item),
                    'cartable_id' => $item->id,
                    'price' => $item->price,
                ]);
                break;

            case 'path':
                $total = $item->courses()
                    ->where('courses.publish', true)
                    ->where('courses.price', '>', 0)
                    ->where('courses.type', '!=', 'free')
                    ->whereNotIn('courses.id', $user->courses->pluck('id')->toArray())
                    ->whereNotIn('courses.id', $user->carts->where('cartable_type', $this->resolveCartableType('course'))->pluck('cartable_id')->toArray())
                    ->sum('courses.price');

                if ($total <= 0) {
                    return response()->json(['error' => 'این مسیر شامل دوره‌های غیرقابل خرید است، یا قبلا به سبد اضافه شده‌اند.'], 422);
                }




                $finalPrice = $total - ($total * $this->discountPercentForPath / 100);

                $user->carts()->create([
                    'cartable_type' => get_class($item),
                    'cartable_id' => $item->id,
                    'price' => $finalPrice,
                    'discount_amount' => $total - $finalPrice,
                ]);
                break;

            default:
                return response()->json(['error' => 'نوع آیتم نامعتبر است.'], 422);
        }

        $cartResponse = $this->cartService->getCartItemsResponse($user);
        return response()->json([
            'message' => 'با موفقیت به سبد اضافه شد.',
            'cartItems' => $cartResponse['items'],
            'total_price' => $cartResponse['total_price'],
            'total_discount' => $cartResponse['total_discount'],
        ], 200);
    }


    public function remove(Request $request, $id)
    {
        $user = auth('api')->user();
        $cart = $user->carts()->findOrFail($id);

        if ($cart) {
            $cart->delete();
        }

        $cartResponse = $this->cartService->getCartItemsResponse($user);
        return response()->json([
            'message' => 'آیتم با موفقیت از سبد حذف شد.',
            'cartItems' => $cartResponse['items'],
            'total_price' => $cartResponse['total_price'],
            'total_discount' => $cartResponse['total_discount'],
        ], 200);
    }

    public function clear(Request $request)
    {
        $user = auth('api')->user();
        $user->carts()->delete();

        return response()->json([
            'message' => 'سبد با موفقیت خالی شد.',
            'cartItems' => [],
            'total_price' => 0,
            'total_discount' => 0,
        ], 200);
    }
}
