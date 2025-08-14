<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Rules\ValidGateway;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment as ShetabitPayment;

class VipController extends Controller
{
    public function index()
    {
        $user = auth('api')->user();
        $activePlan = $user->activeVipPlan();

        if ($activePlan) {
            $activePlan = [
                'title' => $activePlan->title,
                'english_title' => $activePlan->english_title,
                'price' => $activePlan->pivot->price,
                'description' => $activePlan->pivot->description,
                'purchase_type' => $activePlan->pivot->purchase_type,
                'expired_at' => $activePlan->pivot->expired_at,
                'created_at' => $activePlan->pivot->created_at,
                'updated_at' => $activePlan->pivot->updated_at,
            ];
        }

        $expiredPlans = $user->expiredVipPlan()->map(function ($plan) {
            return [
                'title' => $plan->title,
                'english_title' => $plan->english_title,
                'price' => $plan->pivot->price,
                'description' => $plan->pivot->description,
                'purchase_type' => $plan->pivot->purchase_type,
                'expired_at' => $plan->pivot->expired_at,
                'created_at' => $plan->pivot->created_at,
                'updated_at' => $plan->pivot->updated_at,
            ];
        });

        return response()->json([
            'message' => 'success',
            'active_plan' => $activePlan,
            'expired_plans' => $expiredPlans,
        ], 200);
    }

    public function getPlansList()
    {
        $plans = Plan::select('id', 'title', 'english_title', 'icon', 'status', 'popular', 'price', 'period_time', 'description')->get();
        return response()->json(['message' => 'success', 'plans' => $plans], 200);
    }

    public function purchase(Request $request)
    {
        $user = auth('api')->user();

        if ($user->activeVipPlan()) {
            return response()->json(['message' => 'شما هم اکنون یک اشتراک VIP فعال دارید.'], 400);
        }

        $rules = ['plan' => 'required|exists:plans,id'];
        $messages = [
            'plan.required' => 'لطفا یک پلن را انتخاب کنید',
            'plan.exists' => 'پلن انتخابی معتبر نیست',
        ];

        if ($request->input('paymentWay', 'online') === 'online') {
            $rules['gateway'] = ['required', 'string', new ValidGateway];
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error', 'errors' => $validator->errors()->toArray()], 422);
        }

        $plan = Plan::find($request->input('plan'));

        if (!$plan || !$plan->status) {
            return response()->json(['message' => 'پلن انتخابی غیرفعال است'], 400);
        }

        // انتخاب روش پرداخت
        return $this->handlePayment($user, $plan, $request->input('paymentWay'), $request->input('gateway'));
    }

    protected function handlePayment($user, $plan, $paymentWay, $gateway = null)
    {
        if ($paymentWay === 'online') {
            return $this->handleOnlinePayment($user, $plan, $gateway);
        } elseif ($paymentWay === 'wallet') {
            return $this->handleWalletPayment($user, $plan);
        }

        return response()->json(['message' => 'روش پرداخت نامعتبر است.'], 400);
    }

    protected function handleOnlinePayment($user, $plan, $gateway)
    {
        $invoice = new Invoice();
        $invoice->via($gateway)->amount($plan->price);

        $referenceId = $this->generateUniqueReferenceId(Payment::class);

        $payment = ShetabitPayment::via($gateway)
            ->callbackUrl(route('api.vip-callback'))
            ->purchase($invoice, function ($driver, $transactionId) use ($user, $plan, $referenceId, $invoice) {
                $user->payments()->create([
                    'type' => 'vip',
                    'payment_info' => json_encode(['vip' => ['plan_id' => $plan->id, 'price' => $plan->price]]),
                    'driver' => $invoice->getDriver(),
                    'resnumber' => $transactionId,
                    'amount' => $invoice->getAmount(),
                    'reference_id' => $referenceId,
                ]);
            })->pay()->toJson();

        return response()->json(['message' => 'Successfully', 'bank_gateway_url' => json_decode($payment)->action], 200);
    }

    protected function handleWalletPayment($user, $plan)
    {
        if ($user->wallet_balance < $plan->price) {
            return response()->json(['message' => 'موجودی کیف پول شما کافی نیست'], 400);
        }

        $user->plans()->attach($plan->id, [
            'price' => $plan->price,
            'expired_at' => Carbon::now()->addDays($plan->period_time),
            'purchase_type' => 'wallet',
        ]);

        $referenceId = $this->generateUniqueReferenceId(Wallet::class);
        $user->wallets()->create([
            'description' => 'خرید اشتراک VIP (' . $plan->title . ')',
            'amount' => $plan->price,
            'after_balance' => $user->wallet_balance - $plan->price,
            'type' => 'decrease',
            'reference_id' => $referenceId,
        ]);

        $user->update(['wallet_balance' => $user->wallet_balance - $plan->price]);

        return response()->json(['message' => 'اشتراک VIP با موفقیت خریداری شد.'], 200);
    }

    protected function generateUniqueReferenceId($model)
    {
        do {
            $referenceId = random_int(1000000, 9999999);
        } while ($model::where('reference_id', $referenceId)->exists());

        return $referenceId;
    }

    public function vipCallback(Request $request)
    {
        try {
            $authorityParameter = $request->Authority ?? $request->trackId;
            $payment = Payment::where('resnumber', $authorityParameter)->firstOrFail();
            $receipt = ShetabitPayment::via($payment->driver)->amount($payment->amount)->transactionId($authorityParameter)->verify();

            $payment->update(['status' => 1, 'tracking_number' => $receipt->getReferenceId()]);

            $planId = json_decode($payment->payment_info, true)['vip']['plan_id'];
            $plan = Plan::find($planId);

            $payment->user->plans()->attach($plan->id, [
                'price' => $plan->price,
                'expired_at' => Carbon::now()->addDays($plan->period_time),
                'purchase_type' => 'online',
                'payment_id' => $payment->id,
            ]);

            return redirect(env("FRONT_APP_URL") . "/payment/receipt?referenceId={$payment->reference_id}&status=success");
        } catch (InvalidPaymentException $exception) {
            return redirect(env("FRONT_APP_URL") . "/payment/receipt?referenceId={$payment->reference_id}&status=failure");
        }
    }

}
