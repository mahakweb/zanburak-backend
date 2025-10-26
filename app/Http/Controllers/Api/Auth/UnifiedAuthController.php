<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActiveCode;
use App\Models\User;
use App\Models\UserLogin;
use App\Notifications\ActiveCodeNotification;
use App\Notifications\Auth\ActiveCodeEmail;
use Illuminate\Auth\Events\Login;
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
        
        // More strict email validation
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) && 
                   preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $identifier);
        
        // Mobile validation - should start with +98 (Iran) and contain only digits
        $isMobile = !$isEmail && preg_match('/^\+98\d{10}$/', $identifier);

        if (!$isEmail && !$isMobile) {
            return response()->json([
                'message' => 'لطفاً یک ایمیل یا شماره موبایل معتبر وارد کنید.',
                'errors' => ['identifier' => ['فرمت وارد شده صحیح نیست.']]
            ], 422);
        }

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
        
        // More strict email validation
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) && 
                   preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $identifier);
        
        // Mobile validation - should start with +98 (Iran) and contain only digits
        $isMobile = !$isEmail && preg_match('/^\+98\d{10}$/', $identifier);

        if (!$isEmail && !$isMobile) {
            return response()->json([
                'message' => 'لطفاً یک ایمیل یا شماره موبایل معتبر وارد کنید.',
                'errors' => ['identifier' => ['فرمت وارد شده صحیح نیست.']]
            ], 422);
        }

        $activeCode = new ActiveCode();

        if ($alive = $activeCode->getAliveForContact($identifier)) {
            return response()->json([
                'message' => 'OTP already sent',
                'expired_at' => $alive->expired_at,
                'is_new_code' => false,
            ], 200);
        }

        // Set different expiration times: 5 minutes for email, 2 minutes for mobile
        $expireMinutes = $isEmail ? 5 : 2;
        $code = $activeCode->generateCodeForContact($identifier, $expireMinutes);

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
            'expired_at' => now()->addMinutes($expireMinutes),
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

        // More strict email validation
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) && 
                   preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $identifier);
        
        // Mobile validation - should start with +98 (Iran) and contain only digits
        $isMobile = !$isEmail && preg_match('/^\+98\d{10}$/', $identifier);

        if (!$isEmail && !$isMobile) {
            return response()->json([
                'message' => 'لطفاً یک ایمیل یا شماره موبایل معتبر وارد کنید.',
                'errors' => ['identifier' => ['فرمت وارد شده صحیح نیست.']]
            ], 422);
        }

        $activeCode = new ActiveCode();
        $ok = $activeCode->verifyCodeForContact($identifier, $code);
        if (!$ok) {
            return response()->json(['message' => 'کد نامعتبر یا منقضی شده است.'], 422);
        }
        // Purge all OTP codes for this identifier after successful verification
        $activeCode->deleteByContact($identifier);
        $user = $isEmail ? User::where('email', $identifier)->first() : User::where('mobile', $identifier)->first();

        if ($user) {
            if ($user->isDeactivated()) {
                return response()->json([
                    'error_message' => $user->deactivationMessage(),
                    'error_reason' => $user->deactivation_reason,
                    'error_until' => $user->isTemporarilyDeactivated() ? $user->deactivated_until : null,
                ], 403);
            }

            // reset failed attempts and deactivation flags on successful OTP login
            $user->update([
                'failed_login_attempts' => 0,
                'deactivated_until' => null,
                'deactivation_reason' => null,
                'deactivated_by' => null,
            ]);

            $token = $user->createToken($request->userAgent());
            $accessToken = $token->accessToken;
            $accessToken->ip = request()->ip();
            $accessToken->login_type = 'otp';
            $accessToken->save();

            UserLogin::create([
                'user_id' => $user->id,
                'device' => $request->header('User-Agent') ?? 'unknown',
                'ip_address' => request()->ip(),
                'token_id' => $token->accessToken->id,
                'logged_in_at' => now(),
                'login_type' => 'otp',
            ]);

            event(new Login(false, $user, false));

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
        
        // More strict email validation
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) && 
                   preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $identifier);
        
        // Mobile validation - should start with +98 (Iran) and contain only digits
        $isMobile = !$isEmail && preg_match('/^\+98\d{10}$/', $identifier);

        if (!$isEmail && !$isMobile) {
            return response()->json([
                'message' => 'لطفاً یک ایمیل یا شماره موبایل معتبر وارد کنید.',
                'errors' => ['identifier' => ['فرمت وارد شده صحیح نیست.']]
            ], 422);
        }
        
        $user = $isEmail ? User::where('email', $identifier)->first() : User::where('mobile', $identifier)->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            if ($user) {
                $user->increment('failed_login_attempts');
                if ($user->failed_login_attempts >= 5) {
                    $user->update([
                        'deactivated_until' => now()->addHour(),
                        'deactivation_reason' => 'Too many failed login attempts',
                        'deactivated_by' => null,
                        'failed_login_attempts' => 0,
                    ]);
                }
            }
            return response()->json(['message' => 'نام کاربری یا رمز عبور اشتباه است.'], 401);
        }

        if ($user->isDeactivated()) {
            return response()->json([
                'error_message' => $user->deactivationMessage(),
                'error_reason' => $user->deactivation_reason,
                'error_until' => $user->isTemporarilyDeactivated() ? $user->deactivated_until : null,
            ], 403);
        }

        // reset failed attempts and deactivation flags on successful password login
        $user->update([
            'failed_login_attempts' => 0,
            'deactivated_until' => null,
            'deactivation_reason' => null,
            'deactivated_by' => null,
        ]);

        $token = $user->createToken($request->userAgent());
        $accessToken = $token->accessToken;
        $accessToken->ip = request()->ip();
        $accessToken->login_type = 'password';
        $accessToken->save();

        UserLogin::create([
            'user_id' => $user->id,
            'device' => $request->header('User-Agent') ?? 'unknown',
            'ip_address' => $request->ip(),
            'token_id' => $token->accessToken->id,
            'logged_in_at' => now(),
            'login_type' => 'password',
        ]);

        event(new Login(false, $user, false));

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


