<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentItem;
use App\Models\Discount;
use App\Models\Cart;
use App\Models\Course;
use App\Models\Path;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment as ShetabitPayment;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

class PaymentService
{
    public $discountPercentForPath = 40;
    protected $payableMap = [
        'course' => Course::class,
        'path'   => Path::class,
        'vip'    => Plan::class,
    ];

    public function createFromCart($user, array $options = []): Payment
    {
        return DB::transaction(function () use ($user, $options) {
            $carts = $user->carts()->with('cartable')->get();
            if ($carts->isEmpty()) {
                throw new \RuntimeException('سبد خرید خالی است.');
            }

            $payment = Payment::create([
                'user_id'         => $user->id,
                'driver'          => $options['driver'] ?? config('payment.default', 'zarinpal'),
                'amount'          => 0,
                'discount_amount' => 0,
                'discount_code'   => null,
                'expired_at'      => $options['expired_at'] ?? now()->addMinutes(60),
                'status'          => 0,
            ]);

            $total = 0;
            $totalCouponDiscount = 0;
            $appliedCode = $options['discount_code'] ?? null;

            foreach ($carts as $cart) {
                $item = $cart->cartable;
                $baseOriginalPrice = 0;
                $internalDiscountAmount = 0; // e.g., path bundle discount
                $couponDiscountAmount = $cart->discount_id ? (int) ($cart->discount_amount ?? 0) : 0;
                $itemDiscountCode = $cart->discount_id ? ($cart->discount->code ?? null) : null;
                if (!$appliedCode && $itemDiscountCode) {
                    $appliedCode = $itemDiscountCode;
                }

                if ($cart->cartable_type === Path::class) {
                    $path = $cart->cartable;
                    $courseIdsInCart = $user->carts->where('cartable_type', Course::class)->pluck('cartable_id')->toArray();
                    $userCourseIds = $user->courses->pluck('id')->toArray();

                    $availableCourses = $path->courses->where('publish', true)->filter(function ($course) use ($userCourseIds, $courseIdsInCart) {
                        return $course->type !== 'free' && !in_array($course->id, $userCourseIds) && !in_array($course->id, $courseIdsInCart);
                    });

                    $sum = (int) $availableCourses->sum('price');
                    $baseOriginalPrice = $sum; // original sum of courses
                    $finalPriceAfterInternal = (int) round($sum - ($sum * $this->discountPercentForPath / 100));
                    $internalDiscountAmount = $sum - $finalPriceAfterInternal;

                    // apply coupon on top of internal discount
                    $couponDiscountAmount = min($couponDiscountAmount, $finalPriceAfterInternal);
                    $finalPrice = max(0, $finalPriceAfterInternal - $couponDiscountAmount);
                } else {
                    // course or vip
                    $baseOriginalPrice = (int) $item->price;
                    $couponDiscountAmount = min($couponDiscountAmount, $baseOriginalPrice);
                    $finalPrice = max(0, $baseOriginalPrice - $couponDiscountAmount);
                }


                PaymentItem::create([
                    'payment_id'      => $payment->id,
                    'payable_type'    => $cart->cartable_type,
                    'payable_id'      => $cart->cartable_id,
                    'price'           => $baseOriginalPrice,
                    'discount_amount' => $internalDiscountAmount + $couponDiscountAmount,
                    'discount_code'   => $itemDiscountCode,
                    'final_price'     => $finalPrice,
                ]);

                $total += $finalPrice;
                $totalCouponDiscount += $couponDiscountAmount;
            }

            $payment->update([
                'amount'          => $total,
                'discount_amount' => $totalCouponDiscount,
                'discount_code'   => $appliedCode,
            ]);

            return $payment;
        });
    }


    public function startPurchase(Payment $payment, PaymentAttempt $attempt, ?string $callbackUrl = null, array $options = [])
    {
        if ($payment->status && $payment->paid_at) {
            throw new \RuntimeException('این پرداخت قبلاً تکمیل شده است.');
        }
        if ($payment->isExpired()) {
            throw new \RuntimeException('این پرداخت منقضی شده است.');
        }

        $callbackUrl = $callbackUrl ?? route('api.payment.callback', $payment->uuid);

        $driver = $options['driver'] ?? $payment->driver ?? config('payment.default', 'zarinpal');

        $invoice = (new Invoice)->amount((int) $payment->amount);

        return ShetabitPayment::via($driver)
            ->callbackUrl($callbackUrl)
            ->purchase($invoice, function ($driver, $transactionId) use ($payment, $attempt) {
                $attempt->update(['transaction_id' => $transactionId]);
                $payment->update(['tracking_number' => $transactionId, 'resnumber' => $transactionId]);
            });
    }


    public function verify(Payment $payment): array
    {
        try {
            $receipt = ShetabitPayment::via($payment->driver)
                ->amount((int) $payment->amount)
                ->transactionId($payment->tracking_number)
                ->verify();

            $payment->update([
                'status'       => true,
                'paid_at'      => now(),
                'expired_at'   => null,
            ]);

            $this->handleSuccessfulPayment($payment);

            return ['status' => 'paid'];
        } catch (InvalidPaymentException $e) {
            $payment->update(['status' => 'failed']);
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }



    public function handleSuccessfulPayment(Payment $payment): void
    {
        if (! $payment->items) {
            return;
        }

        $user = $payment->user;

        foreach ($payment->items as $item) {
            $payable = $item->payable;

            if (! $payable) {
                continue;
            }

            switch ($item->payable_type) {
                case Course::class:
                    $user->courses()->syncWithoutDetaching([$payable->id]);
                    break;

                case Path::class:
                    // TODO if user later completes the purchase and a new courseis added tho this path during this time, the new course will also be opened forthe user even though they have not paid for it 
                    $courseIdsInPaymentItems = $payment->items->where('payable_type', Course::class)->pluck('payable_id')->toArray();
                    $userCourseIds = $user->courses->pluck('id')->toArray();

                    $availableCourses = $payable->courses->where('publish', true)->filter(function ($course) use ($userCourseIds, $courseIdsInPaymentItems) {
                        return $course->type !== 'free' && !in_array($course->id, $userCourseIds) && !in_array($course->id, $courseIdsInPaymentItems);
                    });

                    foreach ($availableCourses as $course) {
                        $user->courses()->attach($course->id, [
                            'payment_id' => $payment->id,
                            'price' => (int) round($course->price - ($course->price * $this->discountPercentForPath / 100)),
                        ]);
                    }

                    break;

                case Plan::class:
                    $user->plans()->attach($payable->id, [
                        'price' => $payable->price,
                        'expired_at' => Carbon::now()->addDays($payable->period_time),
                        'purchase_type' => 'online',
                        'payment_id' => $payment->id,
                    ]);

                    break;

                default:
                    \Log::warning("نوع ناشناخته در پرداخت: {$item->payable_type}", [
                        'payment_id' => $payment->id,
                        'item_id' => $item->id,
                    ]);
            }
        }

		// Record discount/coupon usage(s) for this successful payment
		$codes = collect();
		if ($payment->discount_code) {
			$codes->push($payment->discount_code);
		}
		foreach ($payment->items as $item) {
			if (!empty($item->discount_code)) {
				$codes->push($item->discount_code);
			}
		}
		$codes = $codes->filter()->unique();
		foreach ($codes as $code) {
			$discount = Discount::where('code', $code)->first();
			if ($discount) {
				$discount->usages()->create([
					'user_id' => $payment->user_id,
					'payment_id' => $payment->id,
					'used_at' => now(),
				]);
			}
		}
    }
}
