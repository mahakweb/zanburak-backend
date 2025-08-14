<?php

namespace App\Http\Controllers\Student\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Payment;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment as ShetabitPayment;
use App\Http\CustomHelpers\GetMessageZarinpal;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
//        alert()->success('موفق','موفقیت')->persistent('');
        $wallets = auth()->user()->wallets()->orderBy('created_at', 'DESC')->get();
        return view('student.wallet.index', compact('wallets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function show(Wallet $wallet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function edit(Wallet $wallet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Wallet $wallet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Wallet  $wallet
     * @return \Illuminate\Http\Response
     */
    public function destroy(Wallet $wallet)
    {
        //
    }



    public function increase(Request $request){
        $request['amount'] = str_replace(',', '', $request['amount']);
        $validData = Validator::make($request->all(), [
            'amount' => ['required', 'numeric', 'min:1000'],
        ]);

        if(!$validData->passes()){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else{



            // Create new invoice.
            $invoice = (new Invoice)->amount($request['amount']);

            $invoice->via('zarinpal'); // set driver -> in config('payment.drivers')

            // $invoice->detail(['key1' => 'value1', 'key2' => 'value2']); // set detail for $invoice

            // Purchase and pay the given invoice.
            // You should use return statement to redirect user to the bank page.
            return ShetabitPayment::callbackUrl(route('student-wallet-callback'))->purchase($invoice, function($driver, $transactionId) use ($request, $invoice) {
                auth()->user()->payments()->create([
                    'type' => 'wallet',
                    'driver' => $invoice->getDriver(),
                    'resnumber' => $transactionId,
                    'amount' => $invoice->getAmount(),
                ]);
                // $invoice->getDetails() // get all details
                // $invoice->getDetail('key1') // get value of key1 in detail
            })->pay()->render();


        }


    }


    public function callback(Request $request){
        // You need to verify the payment to ensure the invoice has been paid successfully.
        // We use transaction id to verify payments
        // It is a good practice to add invoice amount as well.
        try {
            $payment = Payment::where('resnumber', $request->Authority)->firstOrFail();

            $receipt = ShetabitPayment::amount($payment->amount)->transactionId($request->Authority)->verify();


            // You can show payment referenceId to the user.
//            echo $receipt->getReferenceId();

            $payment->update([
                'status' => 1,
                'tracking_number' => $receipt->getReferenceId(),
            ]);
            $wallet = auth()->user()->wallets()->create([
                'description' => 'افزایش موجودی کیف پول',
                'amount' => $payment->amount,
                'after_balance' => auth()->user()->wallet_balance+$payment->amount,
                'type' => 'increase',
                'tracking_number' => $receipt->getReferenceId(),
            ]);

            auth()->user()->update([
                'wallet_balance' => $wallet->after_balance,
            ]);

            alert()->success('تراکنش موفق', 'پرداخت با موفقیت انجام شد')->showConfirmButton('بسیار خوب');
            // return redirect(route('student-wallet'))->withSuccess('پرداخت با موفقیت انجام شد');
            return redirect(route('student-wallet'));

        } catch (InvalidPaymentException $exception) {
            /**
             * when payment is not verified, it will throw an exception.
             * We can catch the exception to handle invalid payments.
             * getMessage method, returns a suitable message that can be used in user interface.
             **/
            alert()->error('تراکنش ناموفق', getMessageZarinpal($exception->getCode()))->showConfirmButton('بسیار خوب');
            // return redirect(route('student-wallet'))->withErrors(getMessageZarinpal($exception->getCode()));
            return redirect(route('student-wallet'));
        }

    }
}
