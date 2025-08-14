<?php

namespace App\Http\Controllers\Student;

use App\Events\Course\GetCourse;
use App\Http\Controllers\Controller;

use App\Models\Course;
use App\Models\Payment;
use App\Notifications\Course\GetCourseNotification;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment as ShetabitPayment;

class CartController extends Controller
{
    public function index(){
        return view('student.cart');
    }


    public function hasItem($sample){
        foreach (Cart::content() as $item) {
            if($item->id->id == $sample->id && get_class($item->id) == get_class($sample)){
                return true;
                break;
            }
        }
        return false;
    }



    public function addToCart(Request $request){

        $course = Course::find($request->item);
        if($this->hasItem($course)){
            return response()->json(['status' => 0, 'msg' => 'data already exist !']);
        }
        Cart::add($course,$course->title,1,$course->price);
        $countItem = Cart::content()->count();
        return response()->json(['status' => 1, 'num' => $countItem, 'msg' => 'data has been successfully added !']);

    }


    public function deleteFromCart(Request $request){
        Cart::remove($request->rowId);
        $countItem = Cart::content()->count();
        return response()->json(['status' => 1, 'priceTotal' => Cart::priceTotal(0, '.', ',') , 'discountTotal' => Cart::discount(0, '.', ','), 'finishPrice' =>  number_format((Cart::priceTotal(0, '', '') - Cart::discount(0, '', '')),  0, '.' , ','), 'num' => $countItem, 'msg' => 'data has been successfully delete !']);

    }

    public function destroyCart(Request $request){
        if(Cart::destroy()){
            return response()->json(['status' => 1, 'msg' => 'data has been successfully destroy !']);
        }

    }

    public function purchase(Request $request){

        // Create new invoice.
        $invoice = new Invoice;
        $price = Cart::priceTotal(0, '', '') - Cart::discount(0, '', '');
        $discount = Cart::discount(0, '', '');
        $invoice->amount($price);
        $invoice->detail(['discount' => $discount]);
        $invoice->via('zarinpal');

        return ShetabitPayment::callbackUrl(route('student-purchase-callback'))->purchase($invoice, function($driver, $transactionId) use ($invoice) {
            auth()->user()->payments()->create([
                'type' => 'course',
                'driver' => $invoice->getDriver(),
                'resnumber' => $transactionId,
                'amount' => $invoice->getAmount(),
                // 'discount' => $invoice->getDetail('discount')
            ]);
        })->pay()->render();


        // $payment = (ShetabitPayment::callbackUrl(route('student-purchase-callback'))->purchase($invoice, function($driver, $transactionId) use ($invoice) {
        //     auth()->user()->payments()->create([
        //         'type' => 'course',
        //         'driver' => $invoice->getDriver(),
        //         'resnumber' => $transactionId,
        //         'amount' => $invoice->getAmount(),
        //         // 'discount' => $invoice->getDetail('discount')
        //     ]);
        // })->pay()->toJson());
        // return (json_decode($payment)->action);

    }

    public function callback(Request $request){

        try {
            $payment = Payment::where('resnumber', $request->Authority)->firstOrFail();

            $receipt = ShetabitPayment::amount($payment->amount)->transactionId($request->Authority)->verify();

            $payment->update([
                'status' => 1,
                'tracking_number' => $receipt->getReferenceId(),
                'discount' => Cart::discount(0, '', '')
            ]);


            foreach (Cart::content() as $item) {
                $course = Course::find($item->id->id);
                auth()->user()->courses()->attach([
                    [
                        'course_id' => $course->id,
                        'payment_id' => $payment->id,
                        'price' => $course->price
                    ]
                ]);
                event(new GetCourse($course));
            }

            Cart::destroy();

//            alert()->success('موفق', 'پرداخت شما با موفقیت انجام شد.'.'/n'.'شماره پیگیری: '.$receipt->getReferenceId());
            alert()->success('موفق', 'پرداخت با موفقیت انجام و دوره‌های خریداری شده با موفقیت برای شما فعال شد، شماره پیگیری: '.$receipt->getReferenceId())->showConfirmButton('بسیار خوب');

            // return redirect(route('cart'))->withSuccess('پرداخت شما با موفقیت انجام شد');
            return redirect(route('cart'));

        } catch (InvalidPaymentException $exception) {
            /**
             * when payment is not verified, it will throw an exception.
             * We can catch the exception to handle invalid payments.
             * getMessage method, returns a suitable message that can be used in user interface.
             **/
//            echo $exception->getMessage();
//            alert()->error('ناموفق', $exception->getMessage());
            alert()->error('تراکنش ناموفق', getMessageZarinpal($exception->getCode()))->showConfirmButton('بسیار خوب');
            // return redirect(route('cart'))->withErrors($exception->getMessage());
            return redirect(route('cart'));
        }

    }

    public function purchaseWithWallet(){

        $cartTotal = Cart::priceTotal(0, '', '') - Cart::discount(0, '', '');

        if (auth()->user()->wallet_balance < $cartTotal){
            alert()->error('ناموفق', 'موجودی کیف پول شما برای انجام این تراکنش کافی نیست.')->showConfirmButton('بسیار خوب');
            // return redirect(route('cart'))->withErrors('موجودی کیف پول کافی نیست');
            return redirect(route('cart'));
        }

        foreach (Cart::content() as $item) {
            $course = Course::find($item->id->id);
            auth()->user()->courses()->attach([$course->id => ['price' => $course->price]]);
            event(new GetCourse($course));
        }


        $wallet = auth()->user()->wallets()->create([
            'description' => 'خرید دوره آموزشی',
            'amount' => $cartTotal,
            'after_balance' => auth()->user()->wallet_balance - $cartTotal,
            'type' => 'decrease',
            'tracking_number' => '',
        ]);

        auth()->user()->update([
            'wallet_balance' => auth()->user()->wallet_balance - $cartTotal
        ]);



        Cart::destroy();
        alert()->success('تراکنش موفق', 'پرداخت با موفقیت انجام و دوره‌های خریداری شده با موفقیت برای شما فعال شد.')->showConfirmButton('بسیار خوب');
        // return redirect(route('cart'))->withSuccess('پرداخت شما با موفقیت انجام شد');
        return redirect(route('cart'));

    }


}
