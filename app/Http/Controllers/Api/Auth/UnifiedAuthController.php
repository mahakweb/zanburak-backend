<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActiveCode;
use App\Models\User;
use App\Notifications\ActiveCodeNotification;
use App\Notifications\Auth\ActiveCodeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UnifiedAuthController extends Controller
{
    // Step 1: identify contact (email or mobile), return status
    public function identify(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $identifier = trim($request->input('identifier'));
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $query = User::query();
        if ($isEmail) {
            $query->where('email', $identifier);
        } else {
            $query->where('mobile', $identifier);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'identifier_type' => $isEmail ? 'email' : 'mobile'
        ]);
    }

    // Step 2: send OTP via email or SMS depending on identifier
    public function sendOtp(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $identifier = trim($request->input('identifier'));
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $activeCode = new ActiveCode();

        if ($alive = $activeCode->getAliveForContact($identifier)) {
            return response()->json([
                'message' => 'OTP already sent',
                'expired_at' => $alive->expired_at,
                'is_new_code' => false,
            ], 200);
        }

        $code = $activeCode->generateCodeForContact($identifier, 3);

        // notify if user exists; otherwise send generic email (no user needed)
        if ($isEmail) {
            $user = User::where('email', $identifier)->first();
            if ($user) {
                $user->notify(new ActiveCodeEmail($code));
            } else {
                // If user not exists yet, we can send to a notifiable route via on-demand notifications
                \Illuminate\Support\Facades\Notification::route('mail', $identifier)
                    ->notify(new ActiveCodeEmail($code));
            }
        } else {
            $user = User::where('mobile', $identifier)->first();
            // for SMS we require a notifiable user; if not exists, create a temporary notifiable proxy
            if (!$user) {
                $user = new User(['mobile' => $identifier, 'email' => 'temp@example.com']);
            }
            $user->notify(new ActiveCodeNotification($code, $identifier));
        }

        return response()->json([
            'message' => 'OTP sent',
            'expired_at' => now()->addMinutes(3),
            'is_new_code' => true,
        ], 200);
    }

    // Step 3: verify OTP
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $identifier = trim($request->input('identifier'));
        $code = (int)$request->input('code');

        $activeCode = new ActiveCode();
        $ok = $activeCode->verifyCodeForContact($identifier, $code);
        if (!$ok) {
            return response()->json(['message' => 'کد نامعتبر یا منقضی شده است.'], 422);
        }

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $user = $isEmail ? User::where('email', $identifier)->first() : User::where('mobile', $identifier)->first();

        if ($user) {
            $token = $user->createToken($request->userAgent());
            $accessToken = $token->accessToken ?? $token->token;
            $accessToken->ip = request()->ip();
            $accessToken->login_type = 'otp';
            $accessToken->save();

            return response()->json([
                'verified' => true,
                'exists' => true,
                'token' => $token->plainTextToken,
                'user' => [
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
                    'last_seen' => $user->last_seen,
                    'active' => $user->active,
                    'is_superuser' => $user->is_superuser,
                    'permissions' => $user->permissions->pluck('name'),
                    'roles' => $user->roles->pluck('name'),
                ],
            ], 200);
        }

        return response()->json([
            'verified' => true,
            'exists' => false,
        ], 200);
    }

    // Optional: password login when user exists
    public function passwordLogin(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identifier = trim($request->input('identifier'));
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $user = $isEmail ? User::where('email', $identifier)->first() : User::where('mobile', $identifier)->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'نام کاربری یا رمز عبور اشتباه است.'], 401);
        }

        $token = $user->createToken($request->userAgent());
        $accessToken = $token->accessToken ?? $token->token;
        $accessToken->ip = request()->ip();
        $accessToken->login_type = 'password';
        $accessToken->save();

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => [
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
                'last_seen' => $user->last_seen,
                'active' => $user->active,
                'is_superuser' => $user->is_superuser,
                'permissions' => $user->permissions->pluck('name'),
                'roles' => $user->roles->pluck('name'),
            ],
        ], 200);
    }
}


