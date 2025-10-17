<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;
use App\Models\Discount;
use App\Services\DiscountService;
use App\Models\Cart;

class DiscountController extends Controller
{
    protected $discountService;
    protected $cartService;

    public function __construct(DiscountService $discountService, CartService $cartService)
    {
        $this->discountService = $discountService;
        $this->cartService = $cartService;
    }

    public function applyDiscount(Request $request)
    {
        $user = auth('api')->user();
        $code = $request->input('code');

        $discount = Discount::where('code', $code)->where('is_active', true)->first();
        if (!$discount) {
            return response()->json(['error' => 'کد تخفیف نامعتبر است.'], 422);
        }

        $carts = $user->carts()->with('cartable')->get();
        if ($carts->isEmpty()) {
            return response()->json(['error' => 'سبد خرید خالی است.'], 422);
        }

        try {
            // ابتدا کد تخفیف قبلی را پاک می‌کنیم و قیمت اصلی را برمی‌گردانیم
            $this->removeExistingDiscounts($user, $carts);

            // Pre-check eligibilities to return specific messages if user/cart doesn't meet requirements
            $this->discountService->checkEligibilities($discount, $user);

            $appliedToAnyItem = false;
            foreach ($carts as $cart) {
                // Only apply to eligible items (if discount targets specific items/categories)
                if (!$this->discountService->isCartItemEligible($discount, $cart, $user)) {
                    continue;
                }

                $result = $this->discountService->apply($discount, $cart, $user);
                $cart->update([
                    'discount_id'     => $discount->id,
                    'discount_amount' => $result['amount'],
                    'price'           => $result['final_price'],
                ]);
                $appliedToAnyItem = true;
            }

            if (!$appliedToAnyItem) {
                return response()->json(['error' => 'این کد برای هیچ‌یک از آیتم‌های سبد شما قابل اعمال نیست.'], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }


        $cartResponse = $this->cartService->getCartItemsResponse($user);
        return response()->json([
            'message' => 'کد تخفیف با موفقیت اعمال شد.',
            'cartItems' => $cartResponse['items'],
            'total_price' => $cartResponse['total_price'],
            'total_discount' => $cartResponse['total_discount'],
        ], 200);
    }

    /**
     * حذف کدهای تخفیف موجود و بازگردانی قیمت اصلی
     */
    private function removeExistingDiscounts($user, $carts)
    {
        foreach ($carts as $cart) {
            $originalPrice = $this->cartService->calculateOriginalPrice($cart);
            $cart->update([
                'discount_id' => null,
                'discount_amount' => 0,
                'price' => $originalPrice
            ]);
        }
    }

    public function removeDiscount(Request $request)
    {
        $user = auth('api')->user();
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
            'message' => 'کد تخفیف حذف شد.',
            'cartItems' => $cartResponse['items'],
            'total_price' => $cartResponse['total_price'],
            'total_discount' => $cartResponse['total_discount'],
        ], 200);
    }

    /**
     * Validate discount code for payment creation
     */
    public function validateDiscount(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'required|numeric|min:0'
        ]);

        $code = $request->input('code');
        $amount = $request->input('amount');

        $discount = Discount::where('code', $code)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if (!$discount) {
            return response()->json([
                'valid' => false,
                'message' => 'کد تخفیف نامعتبر است'
            ], 200);
        }

        // Check minimum amount requirement
        if ($discount->min_amount && $amount < $discount->min_amount) {
            return response()->json([
                'valid' => false,
                'message' => "حداقل مبلغ برای استفاده از این کد تخفیف {$discount->min_amount} تومان است"
            ], 200);
        }

        // Calculate discount amount
        $discountAmount = 0;
        if ($discount->type === 'percentage') {
            $discountAmount = ($amount * $discount->value) / 100;
            if ($discount->max_discount && $discountAmount > $discount->max_discount) {
                $discountAmount = $discount->max_discount;
            }
        } else {
            $discountAmount = $discount->value;
        }

        // Ensure discount doesn't exceed the amount
        if ($discountAmount > $amount) {
            $discountAmount = $amount;
        }

        return response()->json([
            'valid' => true,
            'discount_amount' => $discountAmount,
            'discount_type' => $discount->type,
            'discount_value' => $discount->value,
            'message' => 'کد تخفیف معتبر است'
        ], 200);
    }
}
