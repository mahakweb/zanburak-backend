<?php

namespace App\Http\Controllers\Student\Vip;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment as ShetabitPayment;

class VipController extends Controller
{
    public function index(){


        $expired_plans = auth()->user()->plans;

        $active_plan = auth()->user()->plans()->wherePivot('expired_at' , '>' , Carbon::now())->first();


        return view('student.vip.index', compact(['expired_plans', 'active_plan']));
    }

    public function register (Request $request){

        if(auth()->user()->hasVip()){
            alert()->warning('اخطار!', 'شما اشتراک فعالی دارید و ثبت همزمان دو تا اشتراک وجود ندارد.')->showConfirmButton('بسیار خوب');
            // return back()->withErrors('شما اشتراک فعالی دارید و ثبت همزمان دو تا اشتراک وجود ندارد');
            return redirect()->back();
        }

        $validData = $request->validate([
            'plan' => ['required']
        ]);
        $plan = Plan::findOrFail($validData['plan']);


        switch ($request->input('action')){


            case 'wallet';

                if (auth()->user()->wallet_balance < $plan->price){
                    alert()->error('ناموفق', 'موجودی کیف پول شما برای خرید اشتراک مورد نظر کافی نیست.')->showConfirmButton('بسیار خوب');

                    // return redirect(route('student-vip'))->withErrors('موجودی کیف پول کافی نیست');
                    return redirect(route('student-vip'));
                }

                auth()->user()->plans()->attach($plan->id, ['tracking_number' => '','price' => $plan->price, 'status' => 'enable', 'started_at' => Carbon::now(), 'expired_at' => Carbon::now()->addDays($plan->period_time) ]);


                $wallet = auth()->user()->wallets()->create([
                    'description' => 'خرید اشتراک vip',
                    'amount' => $plan->price,
                    'after_balance' => auth()->user()->wallet_balance - $plan->price,
                    'type' => 'decrease',
                    'tracking_number' => '',
                ]);

                auth()->user()->update([
                    'wallet_balance' => auth()->user()->wallet_balance - $plan->price
                ]);


                alert()->success('موفق', 'اشتراک '.$plan->title.' با موفقیت برای شما فعال شد، از این پس می‌توانید دوره‌هایی که برای اعضای ویژه آزاد هستند را رایگان مشاهده کنید. ')->showConfirmButton('بسیار خوب');

                // return redirect(route('student-vip'))->withSuccess('اشتراک مورد نظر با موفقیت برای شما فعال شد');
                return redirect(route('student-vip'));

            break;
            case 'credit_card';

                // Create new invoice.
                $invoice = new Invoice;
                $invoice->amount($plan->price);
                session(['plan_id' => $plan->id]);
                $invoice->via('zarinpal');

                return ShetabitPayment::callbackUrl(route('student-vip-callback'))->purchase($invoice, function($driver, $transactionId) use ($plan, $invoice) {
                    auth()->user()->payments()->create([
                        'type' => 'vip',
                        'driver' => $invoice->getDriver(),
                        'resnumber' => $transactionId,
                        'amount' => $plan->price,
                    ]);
                })->pay()->render();

            break;

        }


    }

    public function callback(Request $request){
        try {
            $payment = Payment::where('resnumber', $request->Authority)->firstOrFail();

            $receipt = ShetabitPayment::amount($payment->amount)->transactionId($request->Authority)->verify();

           $plan = Plan::find(session()->get('plan_id'));

           $payment->update([
                'status' => 1,
                'tracking_number' => $receipt->getReferenceId(),
           ]);

            auth()->user()->plans()->attach($plan->id, ['payment_id' => $payment->id, 'tracking_number' => $receipt->getReferenceId(),'price' => $plan->price, 'started_at' => Carbon::now(), 'expired_at' => Carbon::now()->addDays($plan->period_time) ]);
//            alert()->success('موفق', 'اشتراک مورد نظر با موفقیت برای شما فعال شد.'.'\n'.'شماره پیگیری: '.$receipt->getReferenceId());

            // return redirect(route('student-vip'))->withSuccess('اشتراک مورد نظر با موفقیت برای شما فعال شد');
            alert()->success('موفق', 'اشتراک '.$plan->title.' با موفقیت برای شما فعال شد، از این پس می‌توانید دوره‌هایی که برای اعضای ویژه آزاد هستند را رایگان مشاهده کنید. ')->showConfirmButton('بسیار خوب');
            return redirect(route('student-vip'));
        } catch (InvalidPaymentException $exception) {
            /**
             * when payment is not verified, it will throw an exception.
             * We can catch the exception to handle invalid payments.
             * getMessage method, returns a suitable message that can be used in user interface.
             **/
//            echo $exception->getMessage();
//            alert()->error('ناموفق', $exception->getMessage());
            alert()->error('تراکنش ناموفق', getMessageZarinpal($exception->getCode()))->showConfirmButton('بسیار خوب');
            // return redirect(route('student-vip'))->withErrors($exception->getMessage());
            return redirect(route('student-vip'));
        }
    }

}
