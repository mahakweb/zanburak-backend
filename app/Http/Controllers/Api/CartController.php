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




    public function index(Request $request)
    {
        $user = auth('api')->user();
        
        // پاک کردن کدهای تخفیف موجود و بازگردانی قیمت اصلی
        $carts = $user->carts()->with('cartable')->get();
        
        // Remove invalid cart items (where cartable no longer exists)
        $invalidCarts = $carts->filter(function ($cart) {
            return is_null($cart->cartable);
        });

        if ($invalidCarts->isNotEmpty()) {
            $invalidCarts->each(function ($cart) {
                $cart->delete();
            });
            
            // Refresh carts after removing invalid ones
            $carts = $user->carts()->with('cartable')->get();
        }
        
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

                if (!app(\App\Services\Course\CourseAvailabilityService::class)->isPurchasable($item)) {
                    return response()->json(['error' => 'این دوره آرشیو شده است و امکان خرید وجود ندارد.'], 422);
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
                    ->notArchived()
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

        // Clean up any invalid cart items before returning response
        $this->cleanInvalidCartItems($user);

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

    /**
     * Clean up invalid cart items (where cartable no longer exists)
     */
    private function cleanInvalidCartItems($user)
    {
        $carts = $user->carts()->with('cartable')->get();
        
        $invalidCarts = $carts->filter(function ($cart) {
            return is_null($cart->cartable);
        });

        if ($invalidCarts->isNotEmpty()) {
            $invalidCarts->each(function ($cart) {
                $cart->delete();
            });
        }
    }
}
