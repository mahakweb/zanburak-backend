<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentItem;
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
                'discount_amount' => $options['discount_amount'] ?? 0,
                'discount_code'   => $options['discount_code'] ?? null,
                'expired_at'      => $options['expired_at'] ?? now()->addMinutes(60),
                'status'          => 0,
            ]);

            $total = 0;

            foreach ($carts as $cart) {
                $item = $cart->cartable;
                $price = (int) $item->price;
                $discountAmount = 0;

                if ($cart->cartable_type === Path::class) {
                    $path = $cart->cartable;
                    $courseIdsInCart = $user->carts->where('cartable_type', Course::class)->pluck('cartable_id')->toArray();
                    $userCourseIds = $user->courses->pluck('id')->toArray();

                    $availableCourses = $path->courses->where('publish', true)->filter(function ($course) use ($userCourseIds, $courseIdsInCart) {
                        return $course->type !== 'free' && !in_array($course->id, $userCourseIds) && !in_array($course->id, $courseIdsInCart);
                    });

                    $sum = $availableCourses->sum('price');
                    $finalPrice = (int) round($sum - ($sum * $this->discountPercentForPath / 100));

                    $price = $finalPrice;
                    $discountAmount = $sum - $finalPrice;
                }


                PaymentItem::create([
                    'payment_id'      => $payment->id,
                    'payable_type'    => $cart->cartable_type,
                    'payable_id'      => $cart->cartable_id,
                    'price'           => $price + $discountAmount,
                    'discount_amount' => $discountAmount,
                    'final_price'     => $price,
                ]);

                $total += $price;
            }

            $finalTotal = max(0, $total - ($options['discount_amount'] ?? 0));

            $payment->update([
                'amount'          => $finalTotal,
                'discount_amount' => $options['discount_amount'] ?? 0,
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
    }
}
