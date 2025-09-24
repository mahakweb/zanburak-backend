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
            $payment->visited_at == null;
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
        $payment = Payment::where('uuid', $uuid)->firstOrFail();
        $result = $this->service->verify($payment);

        $attempt = $payment->attempts()
            ->where('created_at', '>=', now()->subHour()) // because expired_at is 1 hour
            ->latest()
            ->firstOrFail();

        $attempt->update([
            'status' => $result['status'],
            'response_payload' => json_encode($request->all()),
        ]);

        if ($result['status'] === 'paid') {
            // $user = $payment->user;
            // $user->carts()->whereIn('cartable_id', $payment->items->pluck('payable_id'))
            //     ->whereIn('cartable_type', $payment->items->pluck('payable_type'))
            //     ->delete();
        }

        return redirect(env("FRONT_APP_URL") . "/payment/receipt/{$payment->uuid}");
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
