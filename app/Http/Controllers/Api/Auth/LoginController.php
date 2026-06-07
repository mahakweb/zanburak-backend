<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLogin;
use GuzzleHttp\Promise\Create;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Rules\Recaptcha;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password', 'recaptchaToken');

        $validator = Validator::make($credentials, [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string'],
            'recaptchaToken' => ['required', new Recaptcha],
        ], [
            'recaptchaToken.required' => 'گزینه امنیتی من ربات نیستم خاموش می باشد، لطفا از فعال بودن آن اطمینان حاصل نمایید و مجدد امتحان کنید.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validData = $validator->validated();

        $user = User::where('email', $validData['email'])->first();

        if ($user->isDeactivated()) {
            return response()->json([
                'error_message' => $user->deactivationMessage(),
                'error_reason' => $user->deactivation_reason,
                'error_until' => $user->isTemporarilyDeactivated() ? $user->deactivated_until : null,
            ], 403);
        }


        if (!Hash::check($validData['password'], $user->password)) {
            $user->increment('failed_login_attempts');
            if ($user->failed_login_attempts >= 5) {
                $user->update([
                    'deactivated_until' => now()->addHour(),
                    'deactivation_reason' => 'Too many failed login attempts',
                    'deactivated_by' => null,
                    'failed_login_attempts' => 0,
                ]);
            }
            return response()->json([
                'errors' => ['password' => ['ایمیل و گذرواژه با هم تطابق ندارند']]
            ], 401);
        }

        $user->update([
            'failed_login_attempts' => 0,
            'deactivated_until' => null,
            'deactivation_reason' => null,
            'deactivated_by' => null,
        ]);

        $token = $user->createToken($request->userAgent());

        $accessToken = $token->accessToken ?? $token->token;

        $accessToken->ip = request()->ip();
        $accessToken->login_type = 'password';

        $accessToken->save();

        UserLogin::create([
            'user_id' => $user->id,
            'device' => $request->header('User-Agent') ?? 'unknown',
            'ip_address' => $request->ip(),
            'token_id' => $token->accessToken->id,
            'logged_in_at' => now(),
        ]);

        $userData = [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'mobile' => $user->mobile,
            'mobile_verified_at' => $user->mobile_verified_at,
            'username' => $user->username,
            'profile_pic' => $user->profile_pic,
            'cover_pic' => $user->cover_pic,
            'wallet_balance' => $user->wallet_balance,
            'score' => $user->currentScore(),
            'last_seen' => $user->last_seen,
            'active' => $user->active,
            'is_superuser' => $user->is_superuser,
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'roles' => $user->roles->pluck('name'),
        ];

        event(new Login(false, $user, false));
        
        return response()->json(['user' => $userData, 'token' => $token->plainTextToken], 200);

    }

    public function logout(Request $request)
    {
        $user = auth('api')->user();
        $token = $user->currentAccessToken();

        $login = UserLogin::where('token_id', $token->id)->latest()->first();
        if ($login) {
            $login->update([
                'logged_out_at' => now(),
            ]);
        }

        $token->delete();

        return response()->json(['message' => 'با موفقیت خارج شدید']);
    }
}

