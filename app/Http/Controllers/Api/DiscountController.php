<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\DiscountException;
use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;
use App\Services\DiscountService;
use Illuminate\Support\Facades\DB;

class DiscountController extends Controller
{
    public function __construct(
        protected DiscountService $discountService,
        protected CartService $cartService
    ) {
    }

    public function applyDiscount(Request $request)
    {
        $user = auth('api')->user();
        $code = trim((string) $request->input('code'));

        if ($code === '') {
            return response()->json(['error' => 'کد تخفیف را وارد کنید.', 'code' => 'not_found'], 422);
        }

        $carts = $user->carts()->with('cartable')->get();
        if ($carts->isEmpty()) {
            return response()->json(['error' => 'سبد خرید خالی است.', 'code' => 'empty_cart'], 422);
        }

        $previousCoupon = $this->discountService->currentCartCoupon($user);

        try {
            $discount = $this->discountService->findActiveByCode($code);
            $this->discountService->validateForCart($discount, $user);

            DB::transaction(function () use ($user, $discount) {
                $this->discountService->persistCouponOnCart($user, $discount);
            });
        } catch (DiscountException $e) {
            if ($previousCoupon) {
                try {
                    $this->discountService->validateForCart($previousCoupon, $user);
                    $this->discountService->persistCouponOnCart($user, $previousCoupon);
                } catch (\Throwable $ignored) {
                    $this->discountService->persistWithoutCoupon($user);
                }
            }

            return response()->json($e->toArray(), 422);
        } catch (\Exception $e) {
            if ($previousCoupon) {
                try {
                    $this->discountService->persistCouponOnCart($user, $previousCoupon);
                } catch (\Throwable $ignored) {
                    $this->discountService->persistWithoutCoupon($user);
                }
            }

            return response()->json(['error' => $e->getMessage(), 'code' => 'invalid'], 422);
        }

        $cartResponse = $this->cartService->getCartItemsResponse($user);
        $totalDiscount = (int) ($cartResponse['total_coupon_discount'] ?? $cartResponse['total_discount']);

        event(new \App\Events\Discount\DiscountApplied(
            $user,
            "کد تخفیف «{$discount->code}» با موفقیت اعمال شد و {$totalDiscount} تومان از قیمت شما کسر شد.",
            frontendUrl('cart')
        ));

        return response()->json([
            'message' => 'کد تخفیف با موفقیت اعمال شد.',
            'cartItems' => $cartResponse['items'],
            'total_price' => $cartResponse['total_price'],
            'total_original' => $cartResponse['total_original'] ?? $cartResponse['total_price'],
            'total_course_discount' => $cartResponse['total_course_discount'] ?? 0,
            'total_coupon_discount' => $cartResponse['total_coupon_discount'] ?? 0,
            'total_discount' => $cartResponse['total_discount'],
            'final_price' => $cartResponse['final_price'],
            'discount_code' => $cartResponse['discount_code'],
            'applied_coupon' => $cartResponse['applied_coupon'],
        ], 200);
    }

    public function removeDiscount(Request $request)
    {
        $user = auth('api')->user();
        $this->discountService->persistWithoutCoupon($user);

        $cartResponse = $this->cartService->getCartItemsResponse($user);
        return response()->json([
            'message' => 'کد تخفیف حذف شد.',
            'cartItems' => $cartResponse['items'],
            'total_price' => $cartResponse['total_price'],
            'total_original' => $cartResponse['total_original'] ?? $cartResponse['total_price'],
            'total_course_discount' => $cartResponse['total_course_discount'] ?? 0,
            'total_coupon_discount' => $cartResponse['total_coupon_discount'] ?? 0,
            'total_discount' => $cartResponse['total_discount'],
            'final_price' => $cartResponse['final_price'],
            'discount_code' => null,
            'applied_coupon' => null,
        ], 200);
    }

    public function validateDiscount(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = auth('api')->user();

        try {
            $discount = $this->discountService->findActiveByCode($request->input('code'));
            $this->discountService->validateForCart($discount, $user);

            $carts = $user->carts()->with('cartable')->get();
            $couponTotal = 0;
            foreach ($carts as $cart) {
                if ($this->discountService->isCartItemEligible($discount, $cart, $user)) {
                    $pricing = app(\App\Services\PriceCalculator::class)->forCartItem($cart, $discount, $user);
                    $couponTotal += $pricing['coupon_discount_amount'];
                }
            }

            return response()->json([
                'valid' => true,
                'discount_amount' => $couponTotal,
                'discount_type' => $discount->type,
                'discount_value' => $discount->value,
                'stackable' => (bool) $discount->stackable,
                'message' => 'کد تخفیف معتبر است',
            ], 200);
        } catch (DiscountException $e) {
            return response()->json([
                'valid' => false,
                'message' => $e->getMessage(),
                'code' => $e->errorCode,
            ], 200);
        }
    }
}
