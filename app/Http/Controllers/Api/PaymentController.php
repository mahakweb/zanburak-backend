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
        $user = auth('api')->user();
        $data = $request->validate([
            'driver'         => ['nullable', 'string', new ValidGateway()],
            'discount_code'  => ['nullable', 'string'],
            'discount_amount' => ['nullable', 'integer'],
            // 'expired_at'     => ['nullable', 'date'],
        ]);

        try {
            $payment = $this->service->createFromCart($user, $data);

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

            $response = $this->service->startPurchase($payment, $attempt, null, [])->pay()->toJson();
            // clear user cart
            $user->carts()->delete();

            return response()->json(['message' => 'Success!', 'payment_uuid'  => $payment->uuid, 'redirect_url' => json_decode($response)->action], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }


    public function retry(Request $request, $uuid)
    {
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
        ]);


        try {
            $payment->visited_at = null;
            $payment->save();

            // fail all pending attempts
            $payment->attempts()->where('status', 'pending')->update(['status' => 'failed']);


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

            $response = $this->service->startPurchase($payment, $attempt, null, $options)->pay()->toJson();

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
            $result = $this->service->verify($payment);

            // آپدیت وضعیت PaymentAttempt
            if ($attempt) {
                $attempt->update([
                    'status' => $result['status'],
                ]);
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
