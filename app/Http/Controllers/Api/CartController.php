<?php

namespace App\Http\Controllers\Api;

use App\Events\Course\GetCourse;
use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Models\Payment;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;
use Shetabit\Multipay\Invoice;
use Shetabit\Payment\Facade\Payment as ShetabitPayment;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function cartDetails()
    {
        $user = auth('api')->user();
        $cartItems = $user->carts()->with(['course' => function ($query) {
            $query->select('id', 'teacher_id', 'title', 'slug', 'price', 'poster');
            $query->with(['teacher' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'username', 'email');
            }]);
        }])->get();

        return response()->json(['message' => 'Success', 'cartItems' => $cartItems], 200);
    }


    public function addToCart(Request $request, Course $course)
    {
        $user = auth('api')->user();
        $course = Course::findOrFail($course->id);
        // dd($user->carts()->get(['id'])->pluck('id')->toArray());
        if (!in_array($course->id, $user->carts()->pluck('course_id')->toArray())) {
            $user->carts()->create([
                'course_id' => $course->id,
                'qty' => 1,
                'price' => $course->price
            ]);
            // $cartItems = $user->carts()->with(['course' => function ($query) {
            //     $query->select('id', 'title', 'slug', 'price', 'poster');
            // }])->get();

            // return response()->json(['message' => 'Success', 'cartItems' => $cartItems], 200);
        }
        $cartItems = $user->carts()->with(['course' => function ($query) {
            $query->select('id', 'teacher_id', 'title', 'slug', 'price', 'poster');
            $query->with(['teacher' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'username', 'email');
            }]);
        }])->get();
        return response()->json(['message' => 'Success', 'cartItems' => $cartItems], 200);
    }


    public function deleteFromCart(Request $request, Course $course)
    {
        $user = auth('api')->user();
        $course = Course::findOrFail($course->id);
        if ($course) {
            $user->carts()->where('course_id', $course->id)->delete();
            $cartItems = $user->carts()->with(['course' => function ($query) {
                $query->select('id', 'teacher_id', 'title', 'slug', 'price', 'poster');
                $query->with(['teacher' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'username', 'email');
                }]);
            }])->get();

            return response()->json(['message' => 'Success', 'cartItems' => $cartItems], 200);
        }
        $cartItems = $user->carts()->with(['course' => function ($query) {
            $query->select('id', 'teacher_id', 'title', 'slug', 'price', 'poster');
            $query->with(['teacher' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'username', 'email');
            }]);
        }])->get();
        return response()->json(['message' => 'Error', 'cartItems' => $cartItems], 404);
    }


    public function payment(Request $request)
    {
        $user = auth('api')->user();
        $gateway = $request->input('gateway') ? $request->input('gateway') : config('payment.default');
        $invoice = new Invoice;
        $invoice->via($gateway);
        $courses = $user->carts()->with('course')->get();
        $courseData = $courses->map(function ($cart) {
            return [
                'course_id' => $cart->course_id,
                'price' => $cart->course->price,
            ];
        });
        $invoice->amount($courseData->sum('price'));
        // $invoice->amount(1000);


        do {
            $referenceId = random_int(1000000, 9999999);
        } while (Payment::where('reference_id', $referenceId)->exists());

        $payment = ShetabitPayment::via($gateway)->callbackUrl(route('api.payment-callback'))->purchase($invoice, function ($driver, $transactionId) use ($invoice, $courseData, $referenceId) {
            auth('api')->user()->payments()->create([
                'type' => 'course',
                'payment_info' => json_encode(['courses' => $courseData]),
                'driver' => $invoice->getDriver(),
                'resnumber' => $transactionId,
                'amount' => $invoice->getAmount(),
                'reference_id' => $referenceId
            ]);
        })->pay()->toJson();
        return response()->json(['message' => 'Successfully', 'bank_gateway_url' => json_decode($payment)->action], 200);
    }


    public function callback(Request $request)
    {
        try {
            $authorityParameter = $request->Authority ? $request->Authority : $request->trackId;
            $payment = Payment::where('resnumber', $authorityParameter)->firstOrFail();
            $user = $payment->user;

            $receipt = ShetabitPayment::via($payment->driver)->amount($payment->amount)->transactionId($authorityParameter)->verify();
            $payment->update([
                'status' => 1,
                'tracking_number' => $receipt->getReferenceId(),
                // 'discount' => Cart::discount(0, '', '')
            ]);

            $paymentInfo = json_decode($payment->payment_info, true);

            if (isset($paymentInfo['courses'])) {
                $courseIds = [];
                foreach ($paymentInfo['courses'] as $courseData) {
                    $courseId = $courseData['course_id'];
                    array_push($courseIds, $courseId);
                    $price = $courseData['price'];

                    // $course = Course::find($courseId);
                    $user->courses()->attach([
                        [
                            'course_id' => $courseId,
                            'payment_id' => $payment->id,
                            'price' => $price
                        ]
                    ]);
                    $user->carts()->where('course_id', $courseId)->delete();
                    // event(new GetCourse($course)); // error because in api not user found so in this command should send user to event
                }
                $courses = Course::whereIn('id', $courseIds)->get();
                // event(new GetCourse($courses, $user));
            }

            return redirect(env("FRONT_APP_URL")."/payment/receipt?referenceId={$payment->reference_id}&status=success");
        } catch (InvalidPaymentException $exception) {
            // dd($exception);
            return redirect(env("FRONT_APP_URL")."/payment/receipt?referenceId={$payment->reference_id}&status=failure");
            // echo ('تراکنش ناموفق: ' . getMessageZarinpal($exception->getCode()));
            // return redirect(route('cart'))->withErrors($exception->getMessage());
            // return redirect(route('cart'));
        }
    }
}
