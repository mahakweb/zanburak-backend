<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Rules\ValidGateway;
use App\Services\PaymentService;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected PaymentService $service;

    public function __construct(PaymentService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        $data = $request->validate([
            'driver'         => ['nullable', 'string', new ValidGateway()],
			'payment_method' => ['nullable', 'in:wallet,bank'],
            'discount_code'  => ['nullable', 'string'],
            'discount_amount' => ['nullable', 'integer'],
            'digipay_preferred_gateway' => ['nullable', 'integer'],
            'digipay_mode' => ['nullable', 'string', 'in:credit,facilities'],
            // 'expired_at'     => ['nullable', 'date'],
        ]);

        try {
            $payment = $this->service->createFromCart($user, $data);

			// Handle wallet method if requested and amount > 0
			if (($data['payment_method'] ?? 'bank') === 'wallet' && (int) $payment->amount > 0) {
				// Check balance first; if insufficient, remove payment and return error (no payment should persist)
				if ((int) $user->wallet_balance < (int) $payment->amount) {
					// Clean up created payment
					try {
						$payment->items()->delete();
						$payment->delete();
					} catch (\Throwable $cleanupEx) {
						// swallow cleanup exceptions
					}
					return response()->json(['error' => 'موجودی کیف پول شما کافی نیست'], 422);
				}

				// Sufficient: complete via wallet immediately
				$payment->update([
					'payment_method' => 'wallet',
					'status'         => true,
					'paid_at'        => now(),
					'expired_at'     => null,
					'tracking_number'=> null,
				]);

				// Grant access and record coupon usage
				$this->service->handleSuccessfulPayment($payment);

				// Create wallet decrease transaction and update user balance
				$after = (int) $user->wallet_balance - (int) $payment->amount;
				$user->wallets()->create([
					'description'     => 'خرید از کیف پول',
					'amount'          => (int) $payment->amount,
					'after_balance'   => $after,
					'type'            => 'decrease',
					'payment_id'      => $payment->id,
					'tracking_number' => $payment->reference_id,
				]);
				$user->update(['wallet_balance' => $after]);

				// clear user cart
				$user->carts()->delete();

				$redirectUrl = env('FRONT_APP_URL') . "/payment/receipt/{$payment->uuid}";
				return response()->json([
					'message' => 'Success!',
					'status' => 'paid',
					'payment_uuid' => $payment->uuid,
					'redirect_url' => $redirectUrl,
				], 200);
			}

            $attempt = $payment->attempts()->create([
                'attempt_reference' => 'TRY-' . now()->format('YmdHis') . '-' . Str::random(6),
                'payment_uuid'           => $payment->uuid,
                'gateway'           => $data['driver'],
                'status'            => 'pending',
                'request_payload'   => json_encode([
                    'amount'      => $payment->amount,
                    'callbackUrl' => route('api.payment.callback', $payment->uuid),
                    'description' => '',
                    'user_ip'     => request()->ip(),
                    'user_agent'  => request()->userAgent(),
                ]),
            ]);

            // If final amount is zero, finalize immediately and respond with JSON (frontend will redirect)
            if ((int) $payment->amount === 0) {
                // Mark payment as paid (no gateway: empty tracking number, keep model-generated reference_id)
                $payment->update([
                    'status'       => true,
                    'paid_at'      => now(),
                    'expired_at'   => null,
                    'tracking_number' => null,
                ]);

                // Grant user access and record coupon usage
                $this->service->handleSuccessfulPayment($payment);

                // Update attempt as paid
                $attempt->update([
                    'status' => 'paid',
                    'response_payload' => json_encode(['status' => 'paid', 'free' => true]),
                ]);

                // clear user cart
                $user->carts()->delete();

                $redirectUrl = env('FRONT_APP_URL') . "/payment/receipt/{$payment->uuid}";
                return response()->json([
                    'message' => 'Success!',
                    'status' => 'paid',
                    'payment_uuid' => $payment->uuid,
                    'redirect_url' => $redirectUrl,
                ], 200);
            }

            $purchaseOptions = $this->service->buildPurchaseOptionsFromRequest($data);
            $response = $this->service->startPurchase($payment, $attempt, null, $purchaseOptions)->pay()->toJson();
            // clear user cart
            $user->carts()->delete();

            return response()->json(['message' => 'Success!', 'payment_uuid'  => $payment->uuid, 'redirect_url' => json_decode($response)->action], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }


    public function retry(Request $request, $uuid)
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $payment = Payment::where('uuid', $uuid)->firstOrFail();

        if ($payment->status == 1 && $payment->paid_at) {
            return response()->json(['error' => 'این پرداخت قبلاً تکمیل شده است.'], 422);
        }

        if ($payment->isExpired()) {
            return response()->json(['error' => 'این پرداخت منقضی شده است.'], 422);
        }
        if (!$payment->canRetry()) {
            return response()->json(['error' => 'امکان پرداخت مجدد برای این فاکتور وجود ندارد.'], 422);
        }

        $options = $request->validate([
            'driver' => ['nullable', 'string', new ValidGateway()],
            'payment_method' => ['nullable', 'in:wallet,bank'],
            'digipay_preferred_gateway' => ['nullable', 'integer'],
            'digipay_mode' => ['nullable', 'string', 'in:credit,facilities'],
        ]);

        $purchaseOptions = $this->service->buildPurchaseOptionsFromRequest($options);
        try {
            $payment->visited_at = null;
            $payment->save();

            // fail all pending attempts
            $payment->attempts()->where('status', 'pending')->update(['status' => 'failed']);


            // Wallet retry path
            if (($options['payment_method'] ?? 'bank') === 'wallet' && (int) $payment->amount > 0) {
                // Check balance
                if ((int) $user->wallet_balance < (int) $payment->amount) {
                    return response()->json(['error' => 'موجودی کیف پول شما کافی نیست'], 422);
                }

                $payment->update([
                    'payment_method' => 'wallet',
                    'status'         => true,
                    'paid_at'        => now(),
                    'expired_at'     => null,
                    'tracking_number'=> null,
                ]);

                // Grant access
                $this->service->handleSuccessfulPayment($payment);

                // حذف آیتم‌های سبد خرید که در payment_items هستند
                $payment->load('items');
                foreach ($payment->items as $paymentItem) {
                    $user->carts()
                        ->where('cartable_type', $paymentItem->payable_type)
                        ->where('cartable_id', $paymentItem->payable_id)
                        ->delete();
                }

                // Wallet decrease
                $after = (int) $user->wallet_balance - (int) $payment->amount;
                $user->wallets()->create([
                    'description'     => 'خرید از کیف پول',
                    'amount'          => (int) $payment->amount,
                    'after_balance'   => $after,
                    'type'            => 'decrease',
                    'payment_id'      => $payment->id,
                    'tracking_number' => $payment->reference_id,
                ]);
                $user->update(['wallet_balance' => $after]);

                $redirectUrl = env('FRONT_APP_URL') . "/payment/receipt/{$payment->uuid}";
                return response()->json([
                    'message' => 'Success!',
                    'status' => 'paid',
                    'payment_uuid' => $payment->uuid,
                    'redirect_url' => $redirectUrl,
                ], 200);
            }

            // If user chose bank on retry, update payment method/driver before attempting
            if (($options['payment_method'] ?? null) === 'bank') {
                $payment->update([
                    'payment_method' => 'bank',
                    'driver' => $options['driver'] ?? $payment->driver,
                ]);
            }

            $attempt = $payment->attempts()->create([
                'attempt_reference' => 'TRY-' . now()->format('YmdHis') . '-' . Str::random(6),
                'payment_uuid'           => $payment->uuid,
                'gateway'           => $payment->driver,
                'status'            => 'pending',
                'request_payload'   => json_encode([
                    'amount'      => $payment->amount,
                    'callbackUrl' => route('api.payment.callback', $payment->uuid),
                    'description' => '',
                    'user_ip'     => request()->ip(),
                    'user_agent'  => request()->userAgent(),
                ]),
            ]);

            // Zero-amount retry: finalize immediately (frontend will redirect)
            if ((int) $payment->amount === 0) {
                $payment->update([
                    'status'       => true,
                    'paid_at'      => now(),
                    'expired_at'   => null,
                    'tracking_number' => null,
                ]);

                $this->service->handleSuccessfulPayment($payment);

                // حذف آیتم‌های سبد خرید که در payment_items هستند
                $payment->load('items');
                foreach ($payment->items as $paymentItem) {
                    $user->carts()
                        ->where('cartable_type', $paymentItem->payable_type)
                        ->where('cartable_id', $paymentItem->payable_id)
                        ->delete();
                }

                $attempt->update([
                    'status' => 'paid',
                    'response_payload' => json_encode(['status' => 'paid', 'free' => true]),
                ]);

                $redirectUrl = env('FRONT_APP_URL') . "/payment/receipt/{$payment->uuid}";
                return response()->json([
                    'message' => 'Success!',
                    'status' => 'paid',
                    'payment_uuid' => $payment->uuid,
                    'redirect_url' => $redirectUrl,
                ], 200);
            }

            $response = $this->service->startPurchase($payment, $attempt, null, $purchaseOptions)->pay()->toJson();

            return response()->json(['message' => 'Success!', 'payment_uuid'  => $payment->uuid, 'redirect_url' => json_decode($response)->action], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function callback(Request $request, $uuid)
    {
        try {
            $payment = Payment::where('uuid', $uuid)->firstOrFail();
            
            // آپدیت PaymentAttempt قبل از تایید
            $attempt = $payment->attempts()
                ->where('created_at', '>=', now()->subHour())
                ->latest()
                ->first();

            if ($attempt) {
                $attempt->update([
                    'response_payload' => json_encode($request->all()),
                ]);
            }

            // تایید پرداخت (حالا خودکار تشخیص می‌دهد wallet یا cart)
            $result = $this->service->verify($payment, $request->all());

            // آپدیت وضعیت PaymentAttempt
            if ($attempt) {
                $attempt->update([
                    'status' => $result['status'],
                ]);
            }

            // حذف آیتم‌های سبد خرید که در payment_items هستند (فقط برای پرداخت‌های موفق از cart)
            if ($result['status'] === 'paid' && $payment->items()->count() > 0) {
                $payment->load('items');
                $user = $payment->user;
                foreach ($payment->items as $paymentItem) {
                    $user->carts()
                        ->where('cartable_type', $paymentItem->payable_type)
                        ->where('cartable_id', $paymentItem->payable_id)
                        ->delete();
                }
            }

            return redirect(env("FRONT_APP_URL") . "/payment/receipt/{$payment->uuid}");
        } catch (\Exception $exception) {
            // در صورت خطا، سعی کنیم payment را پیدا کنیم
            try {
                $payment = Payment::where('uuid', $uuid)->first();
                if ($payment) {
                    return redirect(env("FRONT_APP_URL") . "/payment/receipt/{$payment->uuid}");
                }
            } catch (\Exception $e) {
                // اگر payment هم پیدا نشد
            }
            
            return redirect(env("FRONT_APP_URL") . "/payment/receipt?status=failure");
        }
    }


    public function receipt(Request $request)
    {
        $uuid = $request->input('uuid');
        $payment = Payment::with(['items.payable', 'attempts'])->where('uuid', $uuid)->firstOrFail();

        $user = auth('api')->user();

        if ($user->id == $payment->user_id) {
            if ($payment->visited_at) {
                return response()->json(['message' => 'Unknown error'], 400);
            }

            $payment->visited_at = now();
            $payment->save();

            $paymentDetail = [
                'id'              => $payment->id,
                'status'          => $payment->status,
                'uuid'            => $payment->uuid,
                'tracking_number' => $payment->tracking_number,
                'reference_id'    => $payment->reference_id,
                'amount'          => $payment->amount,
                'discount_amount' => $payment->discount_amount,
                'driver'          => $payment->driver,
                'paid_at'         => $payment->paid_at,
                'created_at'      => $payment->created_at,
                'updated_at'      => $payment->updated_at,

                'attempts'        => $payment->attempts()->latest()->get(),
                'items'           => $payment->items->map(function ($item) {
                    $base = [
                        'id'             => $item->id,
                        'payable_type'   => class_basename($item->payable_type),
                        'payable_id'     => $item->payable_id,
                        'price'          => $item->price,
                        'discount_amount' => $item->discount_amount,
                        'discount_code'  => $item->discount_code,
                        'final_price'    => $item->final_price,
                    ];

                    if ($item->relationLoaded('payable') && $item->payable) {
                        $payable = $item->payable;

                        if ($payable instanceof \App\Models\Course) {
                            $base['payable'] = [
                                'id'            => $payable->id,
                                'title'         => $payable->title,
                                'english_title' => $payable->english_title,
                                'slug'          => $payable->slug,
                                'poster'        => $payable->poster,
                                'price'         => $payable->price,
                            ];
                        } elseif ($payable instanceof \App\Models\Path) {
                            $base['payable'] = [
                                'id'                => $payable->id,
                                'title'             => $payable->title,
                                'english_title'     => $payable->english_title,
                                'slug'              => $payable->slug,
                                'poster'            => $payable->poster,
                                'icon'              => $payable->icon,
                                'short_description' => $payable->short_description,
                            ];
                        } elseif ($payable instanceof \App\Models\Plan) {
                            $base['payable'] = [
                                'id'            => $payable->id,
                                'title'         => $payable->title,
                                'english_title' => $payable->english_title,
                                'icon'          => $payable->icon,
                                'price'         => $payable->price,
                                'period_time'   => $payable->period_time,
                                'features'      => $payable->features,
                            ];
                        }
                    }

                    return $base;
                }),
            ];

            return response()->json(['message' => 'success', 'detail' => $paymentDetail], 200);
        }

        return response()->json(['message' => 'error: Not found'], 404);
    }


    public function index(Request $request)
    {
        $user = Auth::guard('api')->user();

        $payments = Payment::with('items')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($payments);
    }

    public function show($uuid)
    {
        $user = Auth::guard('api')->user();
        $payment = Payment::with('items')
            ->where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json($payment);
    }
}
