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
use App\Models\Wallet;
use App\Support\GatewayCommission;
use App\Support\PaymentGatewayMetadata;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

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

            $payment = Payment::create(array_merge([
                'user_id'         => $user->id,
                'driver'          => $options['driver'] ?? config('payment.default', 'zarinpal'),
                'payment_method'  => 'bank',
                'amount'          => 0,
                'discount_amount' => 0,
                'discount_code'   => null,
                'expired_at'      => $options['expired_at'] ?? now()->addMinutes(60),
                'status'          => 0,
            ], PaymentGatewayMetadata::applyToPaymentArray($options)));

            $total = 0;
            $totalBase = 0;
            $totalFee = 0;
            $totalCouponDiscount = 0;
            $appliedCode = $options['discount_code'] ?? null;
            $commissionPercent = GatewayCommission::resolvePercent($options);

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


                $feeBreakdown = GatewayCommission::applyToAmount(
                    $finalPrice,
                    GatewayCommission::percentForPayable($commissionPercent, $item, $options)
                );

                PaymentItem::create([
                    'payment_id'          => $payment->id,
                    'payable_type'        => $cart->cartable_type,
                    'payable_id'          => $cart->cartable_id,
                    'price'               => $baseOriginalPrice,
                    'discount_amount'     => $internalDiscountAmount + $couponDiscountAmount,
                    'discount_code'       => $itemDiscountCode,
                    'final_price'         => $finalPrice,
                    'gateway_fee_amount'  => $feeBreakdown['fee_amount'],
                    'charged_price'       => $feeBreakdown['charged_amount'],
                ]);

                $total += $feeBreakdown['charged_amount'];
                $totalBase += $finalPrice;
                $totalFee += $feeBreakdown['fee_amount'];
                $totalCouponDiscount += $couponDiscountAmount;
            }

        $payment->update([
            'amount'              => $total,
            'base_amount'         => $totalBase,
            'gateway_fee_amount'  => $totalFee,
            'gateway_fee_percent' => $commissionPercent,
            'discount_amount'     => $totalCouponDiscount,
            'discount_code'       => $appliedCode,
        ] + PaymentGatewayMetadata::applyToPaymentArray($options));

            return $payment;
        });
    }

    public function recalculateGatewayFees(Payment $payment, array $options): Payment
    {
        $commissionPercent = GatewayCommission::resolvePercent($options);
        $payment->loadMissing('items.payable');

        $total = 0;
        $totalBase = 0;
        $totalFee = 0;

        foreach ($payment->items as $item) {
            $baseAmount = (int) $item->final_price;
            $itemPercent = GatewayCommission::percentForPayable(
                $commissionPercent,
                $item->payable,
                $options
            );
            $feeBreakdown = GatewayCommission::applyToAmount($baseAmount, $itemPercent);

            $item->update([
                'gateway_fee_amount' => $feeBreakdown['fee_amount'],
                'charged_price'      => $feeBreakdown['charged_amount'],
            ]);

            $total += $feeBreakdown['charged_amount'];
            $totalBase += $baseAmount;
            $totalFee += $feeBreakdown['fee_amount'];
        }

        $payment->update([
            'amount'              => $total,
            'base_amount'         => $totalBase,
            'gateway_fee_amount'  => $totalFee,
            'gateway_fee_percent' => $commissionPercent,
            'driver'              => $options['driver'] ?? $payment->driver,
            'payment_method'      => $options['payment_method'] ?? $payment->payment_method,
        ] + PaymentGatewayMetadata::applyToPaymentArray($options));

        return $payment->fresh('items');
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

        if ($driver === 'digipay') {
            $options = $this->mergeDigipayPurchaseOptions($payment, $options);
        }

        $purchaseAmount = (int) ($payment->gateway_paid_amount ?? $payment->amount);

        $gateway = new PaymentGateway($driver);
        $result = $gateway->purchase($purchaseAmount, $callbackUrl, $options);

        // Update attempt and payment with transaction ID
        $transactionId = $result['transaction_id'] ?? $result['authority'] ?? null;
        if ($transactionId) {
            $attempt->update(['transaction_id' => $transactionId]);
            $payment->update(['tracking_number' => $transactionId, 'resnumber' => $transactionId]);
        }

        // Return object with pay() method that returns JSON response
        return new class($result['action'] ?? '') {
            protected string $paymentUrl;

            public function __construct(string $paymentUrl)
            {
                $this->paymentUrl = $paymentUrl;
            }

            public function pay()
            {
                return new class($this->paymentUrl) {
                    protected string $paymentUrl;

                    public function __construct(string $paymentUrl)
                    {
                        $this->paymentUrl = $paymentUrl;
                    }

                    public function toJson()
                    {
                        return json_encode(['action' => $this->paymentUrl]);
                    }
                };
            }
        };
    }


    public function verify(Payment $payment, array $callbackData = []): array
    {
        try {
            $verifyOptions = $this->buildDigipayVerifyOptions($payment, $callbackData);
            $gateway = new PaymentGateway($payment->driver);
            $verifyAmount = (int) ($payment->gateway_paid_amount ?? $payment->amount);
            $result = $gateway->verify($verifyAmount, $payment->tracking_number, $verifyOptions);

            $payment->update([
                'status'       => true,
                'paid_at'      => now(),
                'expired_at'   => null,
                'reference_id' => $result['reference_id'] ?? $payment->reference_id,
            ] + $this->extractDigipaySettlementFields($result));

            if ($payment->driver === 'digipay' && !empty($result['digipay_type'])) {
                $this->handleDigipayPostVerify($payment, $result);
            }

            // Create receipt-like object for compatibility
            $receipt = new class($result) {
                protected array $data;

                public function __construct(array $data)
                {
                    $this->data = $data;
                }

                public function getReferenceId()
                {
                    return $this->data['reference_id'] ?? null;
                }
            };

            // تشخیص نوع پرداخت و پردازش مناسب
            if ($this->isWalletPayment($payment)) {
                $this->handleSuccessfulWalletPayment($payment, $receipt);
            } else {
                if ((int) $payment->wallet_paid_amount > 0) {
                    $this->deductWalletSplitAmount($payment);
                }
                $this->handleSuccessfulPayment($payment);
            }

            return ['status' => 'paid'];
        } catch (Exception $e) {
            $payment->update(['status' => 'failed']);
            
            // ارسال notification پرداخت ناموفق
            event(new \App\Events\Payment\PaymentFailed(
                $payment->user,
                $payment->amount,
                $e->getMessage()
            ));
            
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * تشخیص اینکه آیا پرداخت مربوط به کیف پول است یا نه
     */
    protected function isWalletPayment(Payment $payment): bool
    {
        // اگر description شامل "کیف پول" باشد یا items نداشته باشد
        return str_contains($payment->description ?? '', 'کیف پول') || 
               $payment->items()->count() === 0;
    }



    public function handleSuccessfulPayment(Payment $payment): void
    {
        if (! $payment->items) {
            return;
        }

        $user = $payment->user;
        
        // جمع‌آوری اطلاعات خریدها برای ارسال یک نوتیف واحد
        $courses = [];
        $plans = [];
        $paths = [];

        foreach ($payment->items as $item) {
            $payable = $item->payable;

            if (! $payable) {
                continue;
            }

            switch ($item->payable_type) {
                case Course::class:
                    if (! app(\App\Services\Course\CourseAvailabilityService::class)->isPurchasable($payable)) {
                        break;
                    }
                    // Find the corresponding payment item for this course
                    $itemFinalPrice = $item->final_price ?? $item->price ?? 0;
                    $user->courses()->syncWithoutDetaching([
                        $payable->id => [
                            'price' => $itemFinalPrice,
                            'payment_id' => $payment->id,
                        ]
                    ]);
                    $courses[] = $payable;
                    break;

                case Path::class:
                    // TODO if user later completes the purchase and a new courseis added tho this path during this time, the new course will also be opened forthe user even though they have not paid for it 
                    $courseIdsInPaymentItems = $payment->items->where('payable_type', Course::class)->pluck('payable_id')->toArray();
                    $userCourseIds = $user->courses->pluck('id')->toArray();

                    $availableCourses = $payable->courses->where('publish', true)->filter(function ($course) use ($userCourseIds, $courseIdsInPaymentItems) {
                        return $course->type !== 'free'
                            && ! in_array($course->id, $userCourseIds)
                            && ! in_array($course->id, $courseIdsInPaymentItems)
                            && app(\App\Services\Course\CourseAvailabilityService::class)->isPurchasable($course);
                    });

                    foreach ($availableCourses as $course) {
                        $user->courses()->attach($course->id, [
                            'payment_id' => $payment->id,
                            'price' => (int) round($course->price - ($course->price * $this->discountPercentForPath / 100)),
                        ]);
                    }
                    $paths[] = $payable;
                    break;

                case Plan::class:
                    $user->plans()->attach($payable->id, [
                        'price' => $payable->price,
                        'expired_at' => Carbon::now()->addDays($payable->period_time),
                        'purchase_type' => 'online',
                        'payment_id' => $payment->id,
                    ]);
                    $plans[] = $payable;
                    break;

                default:
                    Log::warning("نوع ناشناخته در پرداخت: {$item->payable_type}", [
                        'payment_id' => $payment->id,
                        'item_id' => $item->id,
                    ]);
            }
        }
        
        // ارسال نوتیف‌های مناسب بر اساس محتویات خرید
        $this->sendPurchaseNotifications($user, $courses, $plans, $paths, $payment);
        
        // Dispatch Mission Purchase Events
        if (count($courses) > 0) {
            foreach ($courses as $course) {
                event(new \App\Events\Mission\PurchaseEvent($user, $course, $payment, $courses));
            }
        }
    }
    
    /**
     * ارسال نوتیف‌های خرید بر اساس محتویات
     */
    private function sendPurchaseNotifications($user, $courses, $plans, $paths, $payment)
    {
        $totalItems = count($courses) + count($plans) + count($paths);
        
        if ($totalItems === 0) {
            return;
        }
        
        // ارسال اطلاع‌رسانی
        // اگر فقط دوره خریداری شده
        if (count($courses) > 0 && count($plans) === 0 && count($paths) === 0) {
            if (count($courses) === 1) {
                // یک دوره
                event(new \App\Events\Payment\CoursePurchased(
                    $user,
                    "دوره «{$courses[0]->title}» با موفقیت برای شما فعال شد.",
                    frontendUrl("course/{$courses[0]->slug}"),
                    'مشاهده دوره'
                ));
            } else {
                // چند دوره
                $courseTitles = collect($courses)->pluck('title')->implode('، ');
                event(new \App\Events\Payment\CoursePurchased(
                    $user,
                    count($courses) . " دوره با موفقیت برای شما فعال شد: {$courseTitles}",
                    frontendUrl('panel/courses'),
                    'مشاهده دوره‌ها'
                ));
            }
        }
        // اگر فقط پلن VIP خریداری شده
        elseif (count($plans) > 0 && count($courses) === 0 && count($paths) === 0) {
            if (count($plans) === 1) {
                event(new \App\Events\Payment\VipUpgraded(
                    $user,
                    "عضویت ویژه «{$plans[0]->title}» با موفقیت برای شما فعال شد.",
                    frontendUrl('panel/vip'),
                    'مشاهده پلن'
                ));
            } else {
                $planTitles = collect($plans)->pluck('title')->implode('، ');
                event(new \App\Events\Payment\VipUpgraded(
                    $user,
                    count($plans) . " پلن عضویت ویژه با موفقیت برای شما فعال شد: {$planTitles}",
                    frontendUrl('panel/vip'),
                    'مشاهده پلن‌ها'
                ));
            }
        }
        // اگر ترکیبی از دوره و پلن
        else {
            $items = [];
            if (count($courses) > 0) {
                $items[] = count($courses) . " دوره";
            }
            if (count($plans) > 0) {
                $items[] = count($plans) . " پلن عضویت ویژه";
            }
            if (count($paths) > 0) {
                $items[] = count($paths) . " مسیر یادگیری";
            }
            
            $itemsText = implode(' و ', $items);
            event(new \App\Events\Payment\CoursePurchased(
                $user,
                "خرید شما با موفقیت انجام شد و {$itemsText} برای شما فعال شد.",
                frontendUrl('panel/courses'),
                'مشاهده خریدها'
            ));
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

	/**
	 * ایجاد پرداخت برای افزایش موجودی کیف پول
	 */
	public function createWalletPayment($user, array $options = []): Payment
	{
		return DB::transaction(function () use ($user, $options) {
			$payment = Payment::create([
				'user_id'         => $user->id,
				'driver'          => $options['driver'] ?? config('payment.default', 'zarinpal'),
                'payment_method'  => 'bank',
				'amount'          => $options['amount'],
				'discount_amount' => 0,
				'discount_code'   => null,
				'expired_at'      => $options['expired_at'] ?? now()->addMinutes(60),
				'status'          => 0,
				'description'     => 'افزایش موجودی کیف پول',
			]);

			return $payment;
		});
	}

	/**
	 * شروع پرداخت کیف پول
	 */
	public function startWalletPurchase(Payment $payment, PaymentAttempt $attempt, ?string $callbackUrl = null, array $options = [])
	{
		if ($payment->status && $payment->paid_at) {
			throw new \RuntimeException('این پرداخت قبلاً تکمیل شده است.');
		}
		if ($payment->isExpired()) {
			throw new \RuntimeException('این پرداخت منقضی شده است.');
		}

		$callbackUrl = $callbackUrl ?? route('api.wallet-callback');
		$driver = $options['driver'] ?? $payment->driver ?? config('payment.default', 'zarinpal');

		if ($driver === 'digipay') {
			$options = $this->mergeDigipayPurchaseOptions($payment, $options);
		}

		$gateway = new PaymentGateway($driver);
		$result = $gateway->purchase((int) $payment->amount, $callbackUrl, $options);

		// Update attempt and payment with transaction ID
		$transactionId = $result['transaction_id'] ?? $result['authority'] ?? null;
		if ($transactionId) {
			$attempt->update(['transaction_id' => $transactionId]);
			$payment->update(['tracking_number' => $transactionId, 'resnumber' => $transactionId]);
		}

		// Return object with pay() method that returns JSON response
		return new class($result['action'] ?? '') {
			protected string $paymentUrl;

			public function __construct(string $paymentUrl)
			{
				$this->paymentUrl = $paymentUrl;
			}

			public function pay()
			{
				return new class($this->paymentUrl) {
					protected string $paymentUrl;

					public function __construct(string $paymentUrl)
					{
						$this->paymentUrl = $paymentUrl;
					}

					public function toJson()
					{
						return json_encode(['action' => $this->paymentUrl]);
					}
				};
			}
		};
	}

	/**
	 * تایید پرداخت کیف پول و ایجاد رکورد wallet
	 */
	public function verifyWalletPayment(Payment $payment, array $callbackData = []): array
	{
		try {
			$verifyOptions = $this->buildDigipayVerifyOptions($payment, $callbackData);
			$gateway = new PaymentGateway($payment->driver);
			$result = $gateway->verify((int) $payment->amount, $payment->tracking_number, $verifyOptions);

			$payment->update([
				'status'       => true,
				'paid_at'      => now(),
				'expired_at'   => null,
				'reference_id' => $result['reference_id'] ?? $payment->reference_id,
			]);

			// Create receipt-like object for compatibility
			$receipt = new class($result) {
				protected array $data;

				public function __construct(array $data)
				{
					$this->data = $data;
				}

				public function getReferenceId()
				{
					return $this->data['reference_id'] ?? null;
				}
			};

			$this->handleSuccessfulWalletPayment($payment, $receipt);

			return ['status' => 'paid'];
		} catch (Exception $e) {
			$payment->update(['status' => 'failed']);
			return ['status' => 'failed', 'error' => $e->getMessage()];
		}
	}

	/**
	 * پردازش پرداخت موفق کیف پول
	 */
	protected function handleSuccessfulWalletPayment(Payment $payment, $receipt): void
	{
		$user = $payment->user;

		// ایجاد رکورد wallet (uuid و reference_id خودکار ایجاد می‌شود)
		$wallet = $user->wallets()->create([
			'description' => 'افزایش موجودی کیف پول',
			'amount' => $payment->amount,
			'after_balance' => $user->wallet_balance + $payment->amount,
			'type' => 'increase',
			'payment_id' => $payment->id,
			'tracking_number' => $receipt->getReferenceId(),
		]);

		// آپدیت موجودی کاربر
		$user->update([
			'wallet_balance' => $wallet->after_balance,
		]);
	}

    public function buildPurchaseOptionsFromRequest(array $data): array
    {
        $options = [];

        if (!empty($data['driver'])) {
            $options['driver'] = $data['driver'];
        }

        if (!empty($data['gateway'])) {
            $options['driver'] = $data['gateway'];
        }

        $driver = $options['driver'] ?? null;
        $hasDirectDigipayRoute = array_key_exists('digipay_preferred_gateway', $data)
            && $data['digipay_preferred_gateway'] !== null;
        $hasDigipayMode = !empty($data['digipay_mode']);
        $isUnified = filter_var($data['digipay_unified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($isUnified) {
            $options['digipay_unified'] = true;
        } elseif ($driver === 'digipay' && !$hasDirectDigipayRoute && !$hasDigipayMode) {
            // Cart/installment flow: default to UPG credit selection unless IPG/wallet is explicit.
            $options['digipay_unified'] = true;
        } elseif ($hasDirectDigipayRoute) {
            $options['digipay_preferred_gateway'] = (int) $data['digipay_preferred_gateway'];
        }

        if ($hasDigipayMode) {
            $options['digipay_mode'] = $data['digipay_mode'];
        }

        if (!empty($data['payment_method'])) {
            $options['payment_method'] = $data['payment_method'];
        }

        return $options;
    }

    public function assertCartSupportsInstallment($user): void
    {
        $carts = $user->carts()->with('cartable')->get();

        $hasEligible = $carts->contains(function ($cart) {
            $item = $cart->cartable;

            return $item && (bool) ($item->allows_installment ?? false);
        });

        if (!$hasEligible) {
            throw new \RuntimeException('هیچ موردی در سبد خرید برای پرداخت اقساطی مجاز نیست.');
        }
    }

    public function prepareWalletSplitPayment(Payment $payment, $user): Payment
    {
        $walletBalance = (int) $user->wallet_balance;
        $total = (int) $payment->amount;
        $walletPart = min($walletBalance, $total);
        $gatewayPart = max(0, $total - $walletPart);

        $payment->update([
            'payment_method' => 'wallet_bank',
            'wallet_paid_amount' => $walletPart,
            'gateway_paid_amount' => $gatewayPart,
        ]);

        return $payment->fresh();
    }

    protected function deductWalletSplitAmount(Payment $payment): void
    {
        $walletPart = (int) $payment->wallet_paid_amount;
        if ($walletPart <= 0) {
            return;
        }

        $user = $payment->user;
        $after = max(0, (int) $user->wallet_balance - $walletPart);

        $user->wallets()->create([
            'description'     => 'خرید ترکیبی (کیف پول + درگاه)',
            'amount'          => $walletPart,
            'after_balance'   => $after,
            'type'            => 'decrease',
            'payment_id'      => $payment->id,
            'tracking_number' => $payment->reference_id,
        ]);

        $user->update(['wallet_balance' => $after]);
    }

    protected function extractDigipaySettlementFields(array $verifyResult): array
    {
        $details = $verifyResult['digipay_details'] ?? null;
        if (!is_array($details)) {
            return [];
        }

        $additional = $details['additionalInfo'] ?? [];
        $currency = config('payment.drivers.digipay.currency', 'T');
        $normalize = static function ($value) use ($currency) {
            $amount = (int) ($value ?? 0);

            return $currency === 'T' ? (int) round($amount / 10) : $amount;
        };

        return [
            'digipay_verify_payload' => $details,
            'digipay_credit_amount' => $normalize($additional['creditAmount'] ?? 0),
            'digipay_cash_amount' => $normalize($additional['cashAmount'] ?? ($additional['prepaymentAmount'] ?? 0)),
            'gateway_paid_amount' => $normalize($details['amount'] ?? 0) ?: null,
            'gateway_variant' => PaymentGatewayMetadata::resolveVariantFromDigipayVerify($details),
        ];
    }

    protected function mergeDigipayPurchaseOptions(Payment $payment, array $options): array
    {
        $payment->loadMissing(['user', 'items.payable']);

        $options = array_merge($options, [
            'mobile' => $payment->user->mobile ?? null,
            'provider_id' => $payment->uuid,
        ]);

        $isWalletTopup = $this->isWalletTopupPayment($payment);
        $digipayMode = $options['digipay_mode'] ?? $payment->digipay_mode ?? null;
        $variant = $payment->gateway_variant;
        $hasCartItems = $payment->items->isNotEmpty();
        $isCartPurchase = $hasCartItems && !$isWalletTopup;
        $isDirectIpg = $variant === 'digipay-ipg'
            || (int) ($options['digipay_preferred_gateway'] ?? $payment->digipay_preferred_gateway ?? -1) === 2;

        $isUnifiedInstallment = $isCartPurchase && !$isDirectIpg && (
            !empty($options['digipay_unified'])
            || $variant === 'digipay-installment'
            || in_array($digipayMode, ['credit', 'facilities'], true)
            || (
                empty($digipayMode)
                && !isset($options['digipay_preferred_gateway'])
                && $payment->digipay_preferred_gateway === null
                && !in_array($variant, ['digipay-wallet', 'digipay-ipg'], true)
            )
        );

        unset($options['digipay_unified'], $options['digipay_mode']);

        if ($isUnifiedInstallment) {
            unset($options['digipay_preferred_gateway'], $options['preferredGateway']);

            if (!$hasCartItems) {
                throw new \RuntimeException('اطلاعات سبد خرید برای پرداخت اقساطی دیجی‌پی یافت نشد.');
            }

            $options['basketDetailsDto'] = $this->buildDigipayBasketDetails($payment);

            return $options;
        }

        if (isset($options['digipay_preferred_gateway'])) {
            $options['preferredGateway'] = (int) $options['digipay_preferred_gateway'];
            unset($options['digipay_preferred_gateway']);
        } elseif ($payment->digipay_preferred_gateway !== null) {
            $options['preferredGateway'] = (int) $payment->digipay_preferred_gateway;
        }

        if ($isCartPurchase && !$isDirectIpg) {
            unset($options['preferredGateway']);
            $options['basketDetailsDto'] = $this->buildDigipayBasketDetails($payment);
        } elseif ($hasCartItems) {
            $options['basketDetailsDto'] = $this->buildDigipayBasketDetails($payment);
        } elseif ($isWalletTopup && !isset($options['preferredGateway'])) {
            $options['basketDetailsDto'] = $this->buildDigipayWalletBasketDetails($payment);
        }

        return $options;
    }

    protected function isWalletTopupPayment(Payment $payment): bool
    {
        if (str_contains($payment->description ?? '', 'کیف پول')) {
            return true;
        }

        if ($payment->relationLoaded('items')) {
            return $payment->items->isEmpty();
        }

        return $payment->items()->count() === 0;
    }

    protected function buildDigipayWalletBasketDetails(Payment $payment): array
    {
        return [
            'basketId' => $payment->uuid,
            'items' => [[
                'sellerId' => 'zanburak',
                'supplierId' => 'zanburak',
                'productCode' => 'wallet-topup',
                'brand' => 'zanburak',
                'productType' => 3,
                'count' => 1,
                'categoryId' => 'service',
            ]],
        ];
    }

    protected function buildDigipayBasketDetails(Payment $payment): array
    {
        $digipayConfig = config('payment.drivers.digipay', []);
        $sellerId = (string) ($digipayConfig['sellerId'] ?? 'zanburak');
        $supplierId = (string) ($digipayConfig['supplierId'] ?? 'zanburak');
        $brand = (string) ($digipayConfig['brand'] ?? 'zanburak');
        $categoryId = (string) ($digipayConfig['categoryId'] ?? 'service');
        $items = [];

        foreach ($payment->items as $index => $item) {
            $payable = $item->payable;
            $items[] = [
                'sellerId' => $sellerId,
                'supplierId' => $supplierId,
                'productCode' => (string) ($payable->id ?? ('item-' . ($index + 1))),
                'brand' => $brand,
                'productType' => 3,
                'count' => 1,
                'categoryId' => $categoryId,
            ];
        }

        if ($items === []) {
            throw new \RuntimeException('سبد خرید دیجی‌پی خالی است.');
        }

        return [
            'basketId' => $payment->uuid,
            'items' => $items,
        ];
    }

    protected function buildDigipayVerifyOptions(Payment $payment, array $callbackData): array
    {
        if ($payment->driver !== 'digipay') {
            return [];
        }

        return [
            'trackingCode' => $callbackData['trackingCode'] ?? null,
            'providerId' => $callbackData['providerId'] ?? $payment->uuid,
            'type' => $callbackData['type'] ?? null,
            'result' => $callbackData['result'] ?? null,
            'callback_amount' => $callbackData['amount'] ?? null,
        ];
    }

    protected function handleDigipayPostVerify(Payment $payment, array $verifyResult): void
    {
        $type = (int) ($verifyResult['digipay_type'] ?? 0);

        if (!in_array($type, [5, 13], true)) {
            return;
        }

        $trackingCode = $verifyResult['reference_id'] ?? null;
        if (empty($trackingCode)) {
            return;
        }

        $products = $payment->items
            ->map(fn ($item) => (string) ($item->payable_id ?? $item->id))
            ->filter()
            ->values()
            ->all();

        if ($products === []) {
            $products = [$payment->uuid];
        }

        try {
            $gateway = new PaymentGateway('digipay');
            $gateway->deliverDigipay($trackingCode, $type, $products, $payment->reference_id);
        } catch (Exception $e) {
            Log::warning('DigiPay deliver failed after verify', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
