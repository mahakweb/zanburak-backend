<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;


class ResetPasswordController extends Controller
{
    public function reset(Request $request)
    {
        $credentials = $request->only('email', 'token', 'password', 'password_confirmation');

        $validator = Validator::make($credentials, [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/', 'confirmed'],
            'token' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = Password::reset(
            $request->only('email', 'token', 'password', 'password_confirmation'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );
        $user = User::where('email', $request->email)->first();
        if ($status == Password::PASSWORD_RESET) {
            event(new PasswordReset($user));
            return response()->json(['message' => 'رمزعبور با موفقیت تغییر کرد.'], 200);
        } elseif ($status == Password::INVALID_TOKEN || $status == Password::TOKEN_EXPIRED) {
            return response()->json(['message' => 'توکن بازنشانی رمزعبور نامعتبر است یا منقضی شده است.'], 400);
        } else {
            return response()->json(['message' => 'بازنشانی رمز عبور ناموفق بود.'], 500);
        }
    }

}
