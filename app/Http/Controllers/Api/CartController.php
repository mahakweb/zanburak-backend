<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\Course\CourseAvailabilityService;
use App\Services\PriceCalculator;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Course;
use App\Models\Path;
use App\Models\Plan;

class CartController extends Controller
{
    protected $typeMap = [
        'course' => Course::class,
        'path' => Path::class,
        'vip' => Plan::class,
    ];

    public function __construct(
        protected CartService $cartService,
        protected PriceCalculator $priceCalculator
    ) {
    }

    protected function resolveCartableType(string $type): string
    {
        $type = strtolower($type);
        if (!isset($this->typeMap[$type])) {
            abort(400, 'نوع آیتم نامعتبر است.');
        }
        return $this->typeMap[$type];
    }

    public function index(Request $request)
    {
        $user = auth('api')->user();
        $cartResponse = $this->cartService->getCartItemsResponse($user);

        return response()->json($this->formatCartResponse($cartResponse, 'Success'), 200);
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

                if (!app(CourseAvailabilityService::class)->isPurchasable($item)) {
                    return response()->json(['error' => 'این دوره آرشیو شده است و امکان خرید وجود ندارد.'], 422);
                }

                $pricing = $this->priceCalculator->forCourse($item);
                $user->carts()->create([
                    'cartable_type' => get_class($item),
                    'cartable_id' => $item->id,
                    'price' => $pricing['final_price'],
                    'discount_amount' => $pricing['total_discount'],
                ]);
                break;

            case 'path':
                $total = $this->priceCalculator->pathOriginalPrice($item, $user);

                if ($total <= 0) {
                    return response()->json(['error' => 'این مسیر شامل دوره‌های غیرقابل خرید است، یا قبلا به سبد اضافه شده‌اند.'], 422);
                }

                $internal = $this->priceCalculator->pathInternalDiscount($total);
                $user->carts()->create([
                    'cartable_type' => get_class($item),
                    'cartable_id' => $item->id,
                    'price' => max(0, $total - $internal),
                    'discount_amount' => $internal,
                ]);
                break;

            default:
                return response()->json(['error' => 'نوع آیتم نامعتبر است.'], 422);
        }

        $cartResponse = $this->cartService->getCartItemsResponse($user);
        return response()->json($this->formatCartResponse($cartResponse, 'با موفقیت به سبد اضافه شد.'), 200);
    }

    public function remove(Request $request, $id)
    {
        $user = auth('api')->user();
        $cart = $user->carts()->findOrFail($id);

        if ($cart) {
            $cart->delete();
        }

        $cartResponse = $this->cartService->getCartItemsResponse($user);
        return response()->json($this->formatCartResponse($cartResponse, 'آیتم با موفقیت از سبد حذف شد.'), 200);
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
            'final_price' => 0,
            'discount_code' => null,
        ], 200);
    }

    public function callback()
    {
        return redirect(frontendUrl('cart'));
    }

    protected function formatCartResponse(array $cartResponse, string $message): array
    {
        return [
            'message' => $message,
            'cartItems' => $cartResponse['items'],
            'total_price' => $cartResponse['total_price'],
            'total_original' => $cartResponse['total_original'] ?? $cartResponse['total_price'],
            'total_course_discount' => $cartResponse['total_course_discount'] ?? 0,
            'total_coupon_discount' => $cartResponse['total_coupon_discount'] ?? 0,
            'total_discount' => $cartResponse['total_discount'],
            'final_price' => $cartResponse['final_price'] ?? max(0, $cartResponse['total_price'] - $cartResponse['total_discount']),
            'discount_code' => $cartResponse['discount_code'] ?? null,
            'applied_coupon' => $cartResponse['applied_coupon'] ?? null,
        ];
    }
}
