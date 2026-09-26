<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActiveCode;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\Info;
use App\Models\Invite;
use App\Events\Mission\InvitationEvent;
use App\Events\Score\User\UserRegistered;
use App\Notifications\ActiveCodeNotification;
use App\Notifications\Auth\ActiveCodeEmail;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Symfony\Component\Mailer\Exception\TransportException;

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

        $user = $query->first();

        if ($user && $user->isDeactivated()) {
            return response()->json(array_merge([
                'exists' => true,
                'identifier_type' => $isEmail ? 'email' : 'mobile',
                'deactivated' => true,
            ], $user->deactivationPayload()), 200);
        }

        $exists = $user !== null;

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

        $user = $isEmail
            ? User::where('email', $identifier)->first()
            : User::where('mobile', $identifier)->first();

        if ($user && $user->isDeactivated()) {
            return $this->deactivationResponse($user);
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

        try {
            if ($isEmail) {
                $user = User::where('email', $identifier)->first();
                if ($user) {
                    $user->notify(new ActiveCodeEmail($code));
                } else {
                    Notification::route('mail', $identifier)
                        ->notify(new ActiveCodeEmail($code));
                }
            } else {
                $user = User::where('mobile', $identifier)->first();
                if (!$user) {
                    $user = new User(['mobile' => $identifier, 'email' => 'temp@example.com']);
                }
                $user->notify(new ActiveCodeNotification($code, $identifier));
            }
        } catch (TransportException $e) {
            Log::error('OTP delivery failed', [
                'identifier' => $identifier,
                'type' => $isEmail ? 'email' : 'mobile',
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $isEmail
                    ? 'ارسال ایمیل با خطا مواجه شد. تنظیمات SMTP سرور را بررسی کنید.'
                    : 'ارسال پیامک با خطا مواجه شد.',
            ], 503);
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
                return $this->deactivationResponse($user);
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
                    'score' => $user->currentScore(),
                    'last_seen' => $user->last_seen,
                    'active' => $user->active,
                    'is_superuser' => $user->is_superuser,
                    'permissions' => $user->getAllPermissions()->pluck('name'),
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
            return $this->deactivationResponse($user);
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
                'score' => $user->currentScore(),
                'last_seen' => $user->last_seen,
                'active' => $user->active,
                'is_superuser' => $user->is_superuser,
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'roles' => $user->roles->pluck('name'),
            ],
        ], 200);
    }

    // Step 4: Register new user after OTP verification
    public function register(Request $request)
    {
        $data = $request->all();

        $identifier = isset($data['identifier']) ? trim($data['identifier']) : null;

        // Derive identifier type
        $isEmailIdentifier = false;
        $isMobileIdentifier = false;

        if ($identifier) {
            $isEmailIdentifier = filter_var($identifier, FILTER_VALIDATE_EMAIL) &&
                preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $identifier);
            $isMobileIdentifier = !$isEmailIdentifier && preg_match('/^\+98\d{10}$/', $identifier);
        }

        // Validate with conditional rules based on identifier type
        $validator = $this->validator($data, $isEmailIdentifier, $isMobileIdentifier);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Normalize email/mobile from identifier when one is missing
        if ($isEmailIdentifier) {
            // Identifier is email; ensure email is set from identifier
            $data['email'] = $identifier;
        } elseif ($isMobileIdentifier) {
            // Identifier is mobile; ensure mobile is set from identifier if not provided
            if (empty($data['mobile'])) {
                $data['mobile'] = $identifier;
            }
        }

        $user = $this->create($data, $isEmailIdentifier, $isMobileIdentifier);

        // OTP during signup already verified the identifier channel — grant score events once.
        if ($isEmailIdentifier) {
            event(new \App\Events\Score\User\EmailVerified($user));
        }
        if ($isMobileIdentifier) {
            event(new \App\Events\Score\User\MobileVerified($user));
        }

        // Handle referral code if provided
        $inviter = null;
        if (!empty($data['referral_code'])) {
            $inviter = User::where('referral_code', $data['referral_code'])->first();
            if ($inviter && $inviter->id !== $user->id) {
                // Create invite record
                Invite::create([
                    'inviter_id' => $inviter->id,
                    'invitee_id' => $user->id,
                    'invite_status' => 'active',
                ]);
            }
        }

        // Create user info
        Info::create([
            'user_id' => $user->id,
        ]);

        // If user came via email (identifier is email), mobile needs verification after registration
        $mobileVerificationRequired = $isEmailIdentifier;

        if ($mobileVerificationRequired) {
            $activeCode = new ActiveCode();
            $expireMinutes = 2; // 2 minutes for mobile OTP
            $code = $activeCode->generateCodeForContact($user->mobile, $expireMinutes);
            
            // Send SMS to the user
            $user->notify(new ActiveCodeNotification($code, $user->mobile));
        }

        // Create token and login
        $token = $user->createToken($request->userAgent());
        $accessToken = $token->accessToken;
        $accessToken->ip = request()->ip();
        $accessToken->login_type = 'register';
        $accessToken->save();

        UserLogin::create([
            'user_id' => $user->id,
            'device' => $request->header('User-Agent') ?? 'unknown',
            'ip_address' => request()->ip(),
            'token_id' => $token->accessToken->id,
            'logged_in_at' => now(),
            'login_type' => 'register',
        ]);

        event(new Registered($user));
        event(new Login(false, $user, false));

        // Dispatch UserRegistered event for score system
        event(new UserRegistered($user, $inviter));

        // Dispatch InvitationEvent if user was invited (for mission system)
        if ($inviter) {
            event(new InvitationEvent($inviter, $user));
        }

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
                'score' => $user->currentScore(),
                'last_seen' => $user->last_seen,
                'active' => $user->active,
                'is_superuser' => $user->is_superuser,
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'roles' => $user->roles->pluck('name'),
            ],
            'mobile_verification_required' => $mobileVerificationRequired,
            'mobile_otp_expired_at' => $mobileVerificationRequired ? now()->addMinutes(2) : null,
        ], 201);
    }

    // Step 5: Verify mobile after registration
    public function verifyMobileAfterRegistration(Request $request)
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^\+98\d{10}$/'],
            'code' => ['required', 'digits:6'],
        ]);

        $mobile = trim($request->input('mobile'));
        $code = (int)$request->input('code');

        $activeCode = new ActiveCode();
        $ok = $activeCode->verifyCodeForContact($mobile, $code);
        
        if (!$ok) {
            return response()->json(['message' => 'کد نامعتبر یا منقضی شده است.'], 422);
        }

        // Find user by mobile
        $user = User::where('mobile', $mobile)->first();
        
        if (!$user) {
            return response()->json(['message' => 'کاربر یافت نشد.'], 404);
        }

        // Check if this is first time verification
        $wasVerifiedBefore = $user->mobile_verified_at;
        
        // Update mobile verification
        $user->update(['mobile_verified_at' => now()]);

        // Purge all OTP codes for this mobile
        $activeCode->deleteByContact($mobile);
        
        // Fire event for points if this is first time verification
        if (!$wasVerifiedBefore) {
            event(new \App\Events\Score\User\MobileVerified($user));
        }

        return response()->json([
            'verified' => true,
            'message' => 'شماره موبایل با موفقیت تایید شد.',
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
                'score' => $user->currentScore(),
                'last_seen' => $user->last_seen,
                'active' => $user->active,
                'is_superuser' => $user->is_superuser,
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'roles' => $user->roles->pluck('name'),
            ],
        ], 200);
    }

    // Forgot password - send reset OTP
    public function sendResetOtp(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
        ]);

        $identifier = trim($request->input('identifier'));
        $normalizedMobile = $this->normalizeIranMobile($identifier);

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) &&
            preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $identifier);
        $isMobile = !$isEmail && $normalizedMobile !== null;

        if (!$isEmail && !$isMobile) {
            return response()->json([
                'message' => 'لطفاً یک ایمیل یا شماره موبایل معتبر وارد کنید.',
                'errors' => ['identifier' => ['فرمت وارد شده صحیح نیست.']]
            ], 422);
        }

        $lookupIdentifier = $isMobile ? $normalizedMobile : $identifier;
        $user = $isEmail ? User::where('email', $lookupIdentifier)->first() : User::where('mobile', $lookupIdentifier)->first();
        if (!$user) {
            return response()->json([
                'message' => 'کاربر با این مشخصات یافت نشد.',
                'errors' => ['identifier' => ['کاربری با این ایمیل/شماره یافت نشد.']]
            ], 404);
        }

        $activeCode = new ActiveCode();
        if ($alive = $activeCode->getAliveForContact($lookupIdentifier)) {
            return response()->json([
                'message' => 'OTP already sent',
                'expired_at' => $alive->expired_at,
                'is_new_code' => false,
            ], 200);
        }

        $expireMinutes = $isEmail ? 5 : 2;
        $code = $activeCode->generateCodeForContact($lookupIdentifier, $expireMinutes);

        if ($isEmail) {
            $user->notify(new ActiveCodeEmail($code));
        } else {
            $user->notify(new ActiveCodeNotification($code, $lookupIdentifier));
        }

        return response()->json([
            'message' => 'OTP sent',
            'expired_at' => now()->addMinutes($expireMinutes),
            'is_new_code' => true,
        ], 200);
    }

    // Forgot password - verify reset OTP
    public function verifyResetOtp(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $identifier = trim($request->input('identifier'));
        $normalizedMobile = $this->normalizeIranMobile($identifier);
        $code = (int)$request->input('code');

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) &&
            preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $identifier);
        $isMobile = !$isEmail && $normalizedMobile !== null;

        if (!$isEmail && !$isMobile) {
            return response()->json([
                'message' => 'لطفاً یک ایمیل یا شماره موبایل معتبر وارد کنید.',
                'errors' => ['identifier' => ['فرمت وارد شده صحیح نیست.']]
            ], 422);
        }

        $lookupIdentifier = $isMobile ? $normalizedMobile : $identifier;
        $user = $isEmail ? User::where('email', $lookupIdentifier)->first() : User::where('mobile', $lookupIdentifier)->first();
        if (!$user) {
            return response()->json([
                'message' => 'کاربر با این مشخصات یافت نشد.',
                'errors' => ['identifier' => ['کاربری با این ایمیل/شماره یافت نشد.']]
            ], 404);
        }

        $activeCode = new ActiveCode();
        $ok = $activeCode->verifyCodeForContact($lookupIdentifier, $code);
        if (!$ok) {
            return response()->json(['message' => 'کد نامعتبر یا منقضی شده است.'], 422);
        }

        // Do not delete code here; allow using it for password reset call
        return response()->json([
            'verified' => true,
        ], 200);
    }

    // Forgot password - reset with OTP
    public function resetPassword(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);

        $identifier = trim($request->input('identifier'));
        $normalizedMobile = $this->normalizeIranMobile($identifier);
        $code = (int)$request->input('code');

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) &&
            preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $identifier);
        $isMobile = !$isEmail && $normalizedMobile !== null;

        if (!$isEmail && !$isMobile) {
            return response()->json([
                'message' => 'لطفاً یک ایمیل یا شماره موبایل معتبر وارد کنید.',
                'errors' => ['identifier' => ['فرمت وارد شده صحیح نیست.']]
            ], 422);
        }

        $lookupIdentifier = $isMobile ? $normalizedMobile : $identifier;
        $user = $isEmail ? User::where('email', $lookupIdentifier)->first() : User::where('mobile', $lookupIdentifier)->first();
        if (!$user) {
            return response()->json([
                'message' => 'کاربر با این مشخصات یافت نشد.',
                'errors' => ['identifier' => ['کاربری با این ایمیل/شماره یافت نشد.']]
            ], 404);
        }

        $activeCode = new ActiveCode();
        $ok = $activeCode->verifyCodeForContact($lookupIdentifier, $code);
        if (!$ok) {
            return response()->json(['message' => 'کد نامعتبر یا منقضی شده است.'], 422);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // Purge all OTP codes for this identifier after successful reset
        $activeCode->deleteByContact($lookupIdentifier);

        // Dispatch password reset event for listeners/logging
        event(new PasswordReset($user));

        return response()->json([
            'message' => 'رمز عبور با موفقیت تغییر یافت.',
        ], 200);
    }

    protected function deactivationResponse(User $user)
    {
        return response()->json(array_merge([
            'message' => $user->deactivationMessage(),
            'deactivated' => true,
        ], $user->deactivationPayload()), 403);
    }

    /**
     * Normalize various Iranian mobile inputs to +98XXXXXXXXXX format.
     * Returns null if cannot normalize to 10-digit national number.
     */
    protected function normalizeIranMobile(string $raw): ?string
    {
        // Keep only digits
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === null || $digits === '') {
            return null;
        }

        // Remove possible prefixes 0098 or 98 or 0
        if (strpos($digits, '0098') === 0) {
            $digits = substr($digits, 4);
        } elseif (strpos($digits, '98') === 0) {
            $digits = substr($digits, 2);
        } elseif (strpos($digits, '0') === 0) {
            $digits = substr($digits, 1);
        }

        // Expect 10 digits national number
        if (strlen($digits) !== 10 || !preg_match('/^\d{10}$/', $digits)) {
            return null;
        }

        return '+98' . $digits;
    }

    /**
     * Get a validator for an incoming registration request.
     */
    protected function validator(array $data, bool $isEmailIdentifier = false, bool $isMobileIdentifier = false)
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'password'   => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/', 'confirmed'],
            'identifier' => ['required', 'string'],
            'referral_code' => ['nullable', 'string', 'max:20', 'exists:users,referral_code'],
        ];

        if ($isEmailIdentifier) {
            // Identifier is email -> ensure uniqueness on users.email, mobile input required
            $rules['identifier'][] = 'email';
            $rules['identifier'][] = 'max:255';
            $rules['identifier'][] = 'unique:users,email';
            $rules['mobile'] = ['required', 'string', 'regex:/^\+98\d{10}$/', 'unique:users,mobile'];
            // Optional email field (if sent redundantly) must match identifier if present
            if (!empty($data['email'])) {
                $rules['email'] = ['string', 'email', 'max:255'];
            }
        } elseif ($isMobileIdentifier) {
            // Identifier is mobile -> ensure uniqueness on users.mobile, email input required
            $rules['identifier'][] = 'regex:/^\+98\d{10}$/';
            $rules['identifier'][] = 'unique:users,mobile';
            $rules['email'] = ['required', 'string', 'email', 'max:255', 'unique:users,email'];
            // Mobile is optional in request body; if present it must be valid and match uniqueness
            if (!empty($data['mobile'])) {
                $rules['mobile'] = ['string', 'regex:/^\+98\d{10}$/', 'unique:users,mobile'];
            }
        } else {
            // Fallback (no/invalid identifier): require both to be safe
            $rules['email'] = ['required', 'string', 'email', 'max:255', 'unique:users,email'];
            $rules['mobile'] = ['required', 'string', 'regex:/^\+98\d{10}$/', 'unique:users,mobile'];
        }

        return Validator::make($data, $rules);
    }

    /**
     * Create a new user instance after a valid registration.
     */
    protected function create(array $data, bool $isEmailIdentifier, bool $isMobileIdentifier)
    {
        $email  = $isEmailIdentifier ? $data['identifier'] : $data['email'];
        $mobile = $isMobileIdentifier ? $data['identifier'] : $data['mobile'];

        $userData = [
            'first_name'  => $data['first_name'],
            'last_name'   => $data['last_name'],
            'email'       => $email,
            'mobile'      => $mobile,
            'username'    => $this->checkUsername($email),
            'password'    => Hash::make($data['password']),
            'role'        => 'student',
            'profile_pic' => 'https://static.zanburak.ir/images/avatar/default.png',
            'cover_pic'   => 'https://static.zanburak.ir/images/cover/default.png',
            'last_seen'   => Carbon::now(),
            'referral_code' => $this->generateReferralCode(),
            // Set verification flags based on identifier type
            'email_verified_at'  => $isEmailIdentifier ? now() : null,
            'mobile_verified_at' => $isMobileIdentifier ? now() : null,
        ];

        return User::create($userData);
    }

    /**
     * Generate a unique referral code for user (6 characters, English letters only)
     */
    protected function generateReferralCode()
    {
        do {
            // Generate 6-character code with only English letters (A-Z)
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= chr(65 + rand(0, 25)); // A-Z
            }
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Generate username based on identifier
     */
    protected function checkUsername($email)
    {
        $username = Str::before($email, '@');
        $check = !! User::where('username', '=', $username)->first();
        return $check ? $username.'_'.time() : $username;
    }
}



