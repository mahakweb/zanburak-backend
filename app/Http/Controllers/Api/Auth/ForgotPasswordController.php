<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Rules\Recaptcha;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;

class ForgotPasswordController extends Controller
{
    public function sendResetLinkEmail(Request $request)
    {
        $credentials = $request->only('email', 'recaptchaToken');

        $validator = Validator::make($credentials, [
            'email' => ['required', 'email', 'exists:users,email'],
            'recaptchaToken' => ['required', new Recaptcha],
        ], [
            'recaptchaToken.required' => 'گزینه امنیتی من ربات نیستم خاموش می باشد، لطفا از فعال بودن آن اطمینان حاصل نمایید و مجدد امتحان کنید.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        $status = Password::broker()->sendResetLink(
            ['email' => $request->email],
            function ($user, $token) {
                $user->notify(new ResetPasswordNotification($token));
            }
        );

        if ($status == Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'لینک بازیابی رمز عبور به ایمیل ' . $request->email . ' ارسال شد.'], 200);
        } elseif ($status == Password::INVALID_USER) {
            return response()->json(['message' => 'کاربری با این ایمیل یافت نشد.'], 404);
        } else {
            return response()->json(['message' => 'ارسال لینک بازنشانی رمز عبور ناموفق بود.'], 500);
        }
    }


    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker();
    }
}
