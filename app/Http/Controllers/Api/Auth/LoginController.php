<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use GuzzleHttp\Promise\Create;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Rules\Recaptcha;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password', 'recaptchaToken');

        $validator = Validator::make($credentials, [
            'email' => ['required', 'email'],
            'password' => ['required'],
            'recaptchaToken' => ['required', new Recaptcha],
        ], [
            'recaptchaToken.required' => 'گزینه امنیتی من ربات نیستم خاموش می باشد، لطفا از فعال بودن آن اطمینان حاصل نمایید و مجدد امتحان کنید.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            $user = auth()->user();
            $token = $user->createToken($request->userAgent());
            $user->tokens()->where('id', $token->accessToken->id)->update(['ip' => $request->ip()]);
            $userData = [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'username' => $user->username,
                'profile_pic' => $user->profile_pic,
                'cover_pic' => $user->cover_pic,
                'wallet_balance' => $user->wallet_balance,
                'last_seen' => $user->last_seen,
            ];
            return response()->json(['user' => $userData, 'token' => $token->plainTextToken], 200);
        } else {
            return response()->json(['errors' => ['password'=> ['ایمیل و گذرواژه با هم تطابق ندارند']]], 401);
        }
    }

    public function logout(Request $request)
    {
        $user = auth('api')->user();
        // dd($user);
        $user->currentAccessToken()->delete();
        return response()->json(['message' => 'با موفقیت خارج شدید']);
    }
}
