<?php

namespace App\Http\Controllers\Api\Panel;

use App\Events\User\ChangeMobile;
use App\Http\Controllers\Controller;
use App\Models\ActiveCode;
use App\Models\Event;
use App\Models\EventGroup;
use App\Models\NotificationPreference;
use App\Models\UserLogin;
use App\Notifications\ActiveCodeNotification;
use App\Rules\CheckCurrentMobile;
use App\Rules\MatchOldPassword;
use App\Rules\PhoneNumberLength;
use App\Rules\UniqueMobile;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Ip2location\IP2LocationLaravel\Facade\IP2LocationLaravel;
use Morilog\Jalali\Jalalian;
use App\Rules\JalalianBirthDateParts;

class ProfileController extends Controller
{
    public function accessTokens(Request $request)
    {
        $user = auth('api')->user();
        $access_tokens = $user->tokens()->select('id', 'name', 'last_used_at', 'ip', 'login_type', 'created_at', 'updated_at')->get();
        $current_token = $user->currentAccessToken()->only('id', 'name', 'last_used_at', 'ip', 'login_type', 'created_at', 'updated_at');
        $current_token['ipInfo'] = collect(IP2LocationLaravel::get($current_token['ip']))->only(['countryName', 'countryCode', 'cityName', 'regionName']);
        foreach ($access_tokens as $accessToken) {
            $accessToken['ipInfo'] = collect(IP2LocationLaravel::get($accessToken['ip']))->only(['countryName', 'countryCode', 'cityName', 'regionName']);
        }
        return response()->json(['message' => 'success', 'access_tokens' => $access_tokens, 'current_token' => $current_token], 200);
    }

    public function terminateSession(Request $request)
    {
        $user = auth('api')->user();
        $currentUserToken = $user->currentAccessToken();

        $token_id = $request->input("id");
        $token = $user->tokens()->where('id', $token_id)->first();

        $currentTime = Carbon::now();
        $currentTokenCreatedAt = Carbon::parse($currentUserToken->created_at);
        $diffInHours = $currentTime->diffInHours($currentTokenCreatedAt);

        if ($token) {
            if ($diffInHours > 6) {

                $login = UserLogin::where('token_id', $token->id)->latest()->first();
                if ($login) {
                    $login->update(['logged_out_at' => now()]);
                }

                $token->delete();
                $access_tokens = $user->tokens()->select('id', 'name', 'last_used_at', 'ip', 'created_at', 'updated_at')->get();
                foreach ($access_tokens as $accessToken) {
                    $accessToken['country'] = collect(IP2LocationLaravel::get($accessToken['ip']))->only(['countryName', 'countryCode', 'cityName', 'regionName']);
                }
                return response()->json(['message' => 'success', 'access_tokens' => $access_tokens], 200);
            } else {

                $tokenCreatedAt = Carbon::parse($token->created_at);
                if ($tokenCreatedAt > $currentTokenCreatedAt) {

                    $login = UserLogin::where('token_id', $token->id)->latest()->first();
                    if ($login) {
                        $login->update(['logged_out_at' => now()]);
                    }

                    $token->delete();
                    $access_tokens = $user->tokens()->select('id', 'name', 'last_used_at', 'ip', 'created_at', 'updated_at')->get();
                    foreach ($access_tokens as $accessToken) {
                        $accessToken['country'] = collect(IP2LocationLaravel::get($accessToken['ip']))->only(['countryName', 'countryCode', 'cityName', 'regionName']);
                    }
                    return response()->json(['message' => 'success', 'access_tokens' => $access_tokens], 200);
                } else {
                    return response()->json(['message' => 'Error: Please use an older session'], 403);
                }

            }
        } else {
            return response()->json(['message' => 'Error: Token not found'], 404);
        }

    }
    public function terminateAllSession(Request $request)
    {
        $user = auth('api')->user();
        $currentUserToken = $user->currentAccessToken();

        $currentTime = Carbon::now();
        $currentTokenCreatedAt = Carbon::parse($currentUserToken->created_at);
        $diffInHours = $currentTime->diffInHours($currentTokenCreatedAt);

        if ($diffInHours > 6) {
            if ($currentUserToken) {
                $tokens = $user->tokens()->whereNot('id', $currentUserToken->id)->get();

                foreach ($tokens as $token) {
                    // ثبت logged_out_at قبل از حذف توکن
                    $login = UserLogin::where('token_id', $token->id)->latest()->first();
                    if ($login) {
                        $login->update(['logged_out_at' => now()]);
                    }
                    $token->delete();
                }
            } else {
                $tokens = $user->tokens()->get();
                foreach ($tokens as $token) {
                    $login = UserLogin::where('token_id', $token->id)->latest()->first();
                    if ($login) {
                        $login->update(['logged_out_at' => now()]);
                    }
                    $token->delete();
                }
            }

            $user->sessions()->delete();
            $access_tokens = $user->tokens()->select('id', 'name', 'last_used_at', 'ip', 'created_at', 'updated_at')->get();
            return response()->json(['message' => 'success', 'access_tokens' => $access_tokens], 200);
        } else {
            $latestToken = $user->tokens()->latest()->first();
            $latestTokenCreatedAt = Carbon::parse($latestToken->created_at);
            if ($latestTokenCreatedAt > $currentTokenCreatedAt) {
                if ($currentUserToken) {
                    $tokens = $user->tokens()->whereNot('id', $currentUserToken->id)->get();
                    foreach ($tokens as $token) {
                        // ثبت logged_out_at قبل از حذف توکن
                        $login = UserLogin::where('token_id', $token->id)->latest()->first();
                        if ($login) {
                            $login->update(['logged_out_at' => now()]);
                        }
                        $token->delete();
                    }
                } else {
                    $tokens = $user->tokens()->get();
                    foreach ($tokens as $token) {
                        $login = UserLogin::where('token_id', $token->id)->latest()->first();
                        if ($login) {
                            $login->update(['logged_out_at' => now()]);
                        }
                        $token->delete();
                    }
                }

                $user->sessions()->delete();
                $access_tokens = $user->tokens()->select('id', 'name', 'last_used_at', 'ip', 'created_at', 'updated_at')->get();
                foreach ($access_tokens as $accessToken) {
                    $accessToken['country'] = collect(IP2LocationLaravel::get($accessToken['ip']))
                        ->only(['countryName', 'countryCode', 'cityName', 'regionName']);
                }
                return response()->json(['message' => 'success', 'access_tokens' => $access_tokens], 200);
            } else {
                return response()->json(['message' => 'Error: Please use an older session'], 403);
            }
        }
    }


    public function getMobileInfo()
    {
        $user = auth('api')->user();
        $userData = $user->only('id', 'mobile', 'mobile_verified_at');
        return response()->json(['message' => 'success', 'data' => $userData], 200);
    }

    public function sendOtp(Request $request)
    {
        $user = auth('api')->user();
        $country_dial_code = $request->input('country_dial_code');
        $mobile = $request->input('mobile');
        $verifyCurrentMobile = $request->input('is_verifying_current_mobile', false);
        $fullMobile = $verifyCurrentMobile ? $user->mobile : $country_dial_code . $mobile;
        if (!$verifyCurrentMobile) {
            $country_code = $request->input('country_code');
            $validData = Validator::make($request->all(), [
                'country_code' => ['required', 'string'],
                'mobile' => [
                    'required',
                    'string',
                    new UniqueMobile($user->id, $country_dial_code),
                    new CheckCurrentMobile($user->mobile, $country_dial_code),
                    new PhoneNumberLength($country_code),
                ],
            ]);
            if (!$validData->passes()) {
                return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
            }
        }

        // $remainingTime = 0;
        $isNewCode = false;
        if ($activeCode = (new ActiveCode())->getAliveCodeForUser($fullMobile)) {
            // $remainingTime = $activeCode->expired_at->diffInSeconds(Carbon::now());
            return response()->json([
                'message' => 'OTP already sent for your number',
                // 'remaining_time' => $remainingTime,
                'expired_at' => $activeCode->expired_at,
                'is_new_code' => $isNewCode,
            ], 200);
        }
        $code = ActiveCode::genereteCode($fullMobile, 3); // for 3 minute
        $isNewCode = true;
        $user->notify(new ActiveCodeNotification($code, $fullMobile));
        return response()->json([
            'message' => 'New OTP was sent to your number',
            // 'remaining_time' => 180, // for 3 minute
            'expired_at' => Carbon::now()->addMinutes(3), // for 3 minute
            'is_new_code' => $isNewCode,
        ], 200);

    }

    public function verifyOtp(Request $request)
    {
        $user = auth('api')->user();
        $country_dial_code = $request->input('country_dial_code');
        $mobile = $request->input('mobile');
        $verifyCurrentMobile = $request->input('is_verifying_current_mobile', false);
        $fullMobile = $verifyCurrentMobile ? $user->mobile : $country_dial_code . $mobile;
        if ($verifyCurrentMobile) {
            $validData = Validator::make($request->all(), [
                'otp' => ['required', 'numeric', 'digits:6'],
            ]);
        } else {
            $country_code = $request->input('country_code');
            $validData = Validator::make($request->all(), [
                'otp' => ['required', 'numeric', 'digits:6'],
                'country_code' => ['required', 'string'],
                'mobile' => [
                    'required',
                    'string',
                    new UniqueMobile($user->id, $country_dial_code),
                    new CheckCurrentMobile($user->mobile, $country_dial_code),
                    new PhoneNumberLength($country_code),
                ],
            ]);
        }

        if (!$validData->passes()) {
            return response()->json(['message' => 'Error in validation parameters!', 'errors' => $validData->errors()->toArray()], 422);
        } else {
            $validData = $validData->validated();

            $status = ActiveCode::verifyCode($fullMobile, $validData['otp']);

            if (!$status) {
                return response()->json(['message' => 'Error!', 'errors' => ['otp' => ['کد وارد شده صحیح نیست.']]], 422);
            }

            $update = $user->update([
                'mobile' => $fullMobile,
                'mobile_verified_at' => now()
            ]);

            ActiveCode::where('user_phone', $fullMobile)->delete();

            event(new ChangeMobile($user));

            return response()->json(['message' => 'Mobile number was successfully changed', 'new_mobile' => $fullMobile, 'mobile_verified_at' => now()], 200);
        }
    }

    public function changePassword(Request $request)
    {
        $user = auth('api')->user();
        $validData = Validator::make($request->all(), [
            'oldPassword' => ['required', new MatchOldPassword],
            'newPassword' => ['required', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/', 'different:old-password'],
        ]);

        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        } else {
            $updateData = $user->update([
                'password' => Hash::make($request->input('newPassword')),
            ]);
            if ($updateData) {
                event(new PasswordReset($user));
                return response()->json(['message' => 'Success: Password has been updated.'], 200);
            }

        }
    }

    public function getUserData()
    {
        $current_user = auth('api')->user();
        $user = $current_user->only(['first_name', 'last_name', 'username', 'email', 'profile_pic', 'cover_pic']);
        $userInfo = $current_user->info->only(['birth_date', 'job', 'about', 'website', 'github', 'linkedin', 'telegram', 'instagram', 'twitter']);
        // $userData['info'] = $userInfo;
        if (!empty($userInfo['birth_date'])) {
            $userInfo['birth_date'] = Jalalian::fromCarbon(new \Carbon\Carbon($userInfo['birth_date']))->format('Y/m/d');
        } else {
            $userInfo['birth_date'] = null;
        }

        return response()->json(['message' => 'Success', 'user' => $user, 'info' => $userInfo], 200);
    }
    public function updateProfile(Request $request)
    {
        $current_user = auth('api')->user();
        $validData = Validator::make($request->all(), [
            'user.first_name' => ['required', 'string', 'max:255', 'min:3'],
            'user.last_name' => ['required', 'string', 'max:255', 'min:3'],
            'user.username' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($current_user->id)],
            'info.birth_date' => ['required', new JalalianBirthDateParts()],
            'info.job' => ['required', 'string', 'max:255', 'min:3'],
            'info.about' => ['required', 'string', 'max:1000', 'min:10'],
            'info.website' => ['nullable', 'url', 'max:500'],
            'info.github' => ['nullable', 'max:255'],
            'info.telegram' => ['nullable', 'max:255'],
            'info.instagram' => ['nullable', 'max:255'],
            'info.linkedin' => ['nullable', 'max:255'],
            'info.twitter' => ['nullable', 'max:255'],
        ]);
        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        } else {
            $validatedData = $validData->validated();
            $userData = $validatedData['user'];
            $infoData = $validatedData['info'];

            if ($infoData['birth_date']) {
                $infoData['birth_date'] = Jalalian::fromFormat('Y/m/d', $infoData['birth_date'])->toCarbon()->format('Y-m-d');
            }

            $updateUser = $current_user->update($userData);
            $updateInfo = $current_user->info()->update($infoData);

            if ($updateUser && $updateInfo) {
                return response()->json(['message' => 'Success: Data has been updated.'], 200);
            }

        }
    }

    public function changeProfilePic(Request $request)
    {
        $user = auth('api')->user();
        $validData = Validator::make($request->all(), [
            'profilePic' => ['required', 'image', 'max:10240'],
        ]);
        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        } else {
            // $prev_pic = parse_url($user->profile_pic)['path'];
            $default_pic = "https://static.zanburak.ir/images/avatar/default.png";
            $prev_pic = $user->profile_pic;
            $prev_pic_parsed = parse_url($user->profile_pic);
            $storagePath = Storage::disk('static')->url('');
            $path = Storage::disk('static')->put('/images/avatar/' . now()->year . '/' . now()->month . '/' . now()->day, $request->profilePic);
            $new_pic = $storagePath . $path;
            if ($prev_pic != $default_pic && Storage::disk('static')->exists($prev_pic_parsed['path'])) {
                Storage::disk('static')->delete($prev_pic_parsed['path']);
            }
            $updateData = $user->update([
                'profile_pic' => $new_pic,
            ]);
            return response()->json(['message' => 'Success: Profile picture has been updated.', 'profilePic' => $new_pic], 200);
        }
    }
    public function changeCoverPic(Request $request)
    {
        $user = auth('api')->user();
        $validData = Validator::make($request->all(), [
            'coverPic' => ['required', 'image', 'max:10240'],
        ]);
        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        } else {
            // $prev_pic = parse_url($user->cover_pic)['path'];
            $default_pic = "https://static.zanburak.ir/images/cover/default.png";
            $prev_pic = $user->cover_pic;
            $prev_pic_parsed = parse_url($user->cover_pic);
            $storagePath = Storage::disk('static')->url('');
            $path = Storage::disk('static')->put('/images/cover/' . now()->year . '/' . now()->month . '/' . now()->day, $request->coverPic);
            $new_pic = $storagePath . $path;
            if ($prev_pic != $default_pic && Storage::disk('static')->exists($prev_pic_parsed['path'])) {
                Storage::disk('static')->delete($prev_pic_parsed['path']);
            }
            $updateData = $user->update([
                'cover_pic' => $new_pic,
            ]);
            return response()->json(['message' => 'Success: Cover picture has been updated.', 'coverPic' => $new_pic], 200);
        }
    }

    public function getUserPreferences()
    {
        $user = auth('api')->user();
        $userId = $user->id;
        $eventGroups = EventGroup::with(['events'])->get();

        $groupedPreferences = [];

        foreach ($eventGroups as $group) {
            $groupData = [
                'group_title' => $group->title,
                'group_english_title' => $group->english_title,
                'group_slug' => $group->slug,
                'group_icon' => $group->icon,
                'group_description' => $group->description,
                'events' => []
            ];

            foreach ($group->events as $event) {
                $preference = NotificationPreference::where('user_id', $userId)
                    ->where('event_id', $event->id)
                    ->first();

                $groupData['events'][] = [
                    'event_id' => $event->id,
                    'event_title' => $event->title,
                    'event_english_title' => $event->english_title,
                    'event_slug' => $event->slug,
                    'event_icon' => $event->icon,
                    'event_description' => $event->description,
                    'via_email' => $preference->via_email ?? $event->is_email_enabled,
                    'via_sms' => $preference->via_sms ?? $event->is_sms_enabled,
                    'via_telegram' => $preference->via_telegram ?? $event->is_telegram_enabled,
                    'via_site' => $preference->via_site ?? $event->is_site_enabled,
                    'is_email_enabled' => $event->is_email_enabled,
                    'is_sms_enabled' => $event->is_sms_enabled,
                    'is_telegram_enabled' => $event->is_telegram_enabled,
                    'is_site_enabled' => $event->is_site_enabled
                ];
            }

            $groupedPreferences[] = $groupData;
        }

        // اگر notifications_enabled null باشد، به صورت پیش‌فرض true در نظر می‌گیریم
        $notificationsEnabled = $user->notifications_enabled !== null ? (bool)$user->notifications_enabled : true;
        
        return response()->json([
            'message' => 'Success', 
            'grouped_preferences' => $groupedPreferences,
            'notifications_enabled' => $notificationsEnabled
        ], 200);
    }

    public function updateSinglePreference(Request $request)
    {
        $user = auth('api')->user();
        $userId = $user->id;
        $eventId = $request->input('eventId');
        $field = $request->input('field');
        $value = $request->input('value');

        $event = Event::findOrFail($eventId);

        if (!$event->isChannelEnabled($field)) {
            return response()->json(['message' => 'This channel is disabled by admin.'], 403);
        }

        $notificationPreference = NotificationPreference::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->first();

        if (!$notificationPreference) {
            $preferencesData = [
                'user_id' => $userId,
                'event_id' => $eventId,
                'via_email' => $event->is_email_enabled ? true : false,
                'via_sms' => $event->is_sms_enabled ? true : false,
                'via_telegram' => $event->is_telegram_enabled ? true : false,
                'via_site' => $event->is_site_enabled ? true : false,
            ];

            $notificationPreference = NotificationPreference::create($preferencesData);

            $notificationPreference->$field = $value;
            $notificationPreference->save();

            return response()->json(['message' => 'Record created and updated', 'notification_preference' => $notificationPreference], 201);
        } else {
            $notificationPreference->$field = $value;
            $notificationPreference->save();

            return response()->json(['message' => 'Record updated', 'notification_preference' => $notificationPreference], 200);
        }
    }

    /**
     * Toggle کردن همه اطلاع‌رسانی‌ها (Global Toggle)
     */
    public function toggleAllNotifications(Request $request)
    {
        $user = auth('api')->user();
        
        // اگر null باشد، به صورت پیش‌فرض true در نظر می‌گیریم
        $currentValue = $user->notifications_enabled !== null ? (bool)$user->notifications_enabled : true;
        $user->notifications_enabled = !$currentValue;
        $user->save();

        return response()->json([
            'message' => $user->notifications_enabled ? 'All notifications enabled' : 'All notifications disabled',
            'notifications_enabled' => (bool)$user->notifications_enabled
        ], 200);
    }

    /**
     * به‌روزرسانی دسته‌جمعی تنظیمات (Bulk Update)
     */
    public function bulkUpdatePreferences(Request $request)
    {
        $user = auth('api')->user();
        $userId = $user->id;
        
        $validData = Validator::make($request->all(), [
            'action' => ['required', 'in:enable_all,disable_all,enable_channel,disable_channel,reset_to_default'],
            'channel' => ['required_if:action,enable_channel,disable_channel', 'in:via_email,via_sms,via_telegram,via_site'],
            'event_ids' => ['nullable', 'array'],
            'event_ids.*' => ['exists:events,id']
        ]);

        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        }

        $action = $request->input('action');
        $channel = $request->input('channel');
        $eventIds = $request->input('event_ids', []);

        // دریافت همه Event ها
        $events = Event::when(!empty($eventIds), function($query) use ($eventIds) {
            return $query->whereIn('id', $eventIds);
        })->get();

        $updatedCount = 0;

        foreach ($events as $event) {
            $preference = NotificationPreference::where('user_id', $userId)
                ->where('event_id', $event->id)
                ->first();

            if (!$preference) {
                $preference = NotificationPreference::create([
                    'user_id' => $userId,
                    'event_id' => $event->id,
                    'via_email' => $event->is_email_enabled,
                    'via_sms' => $event->is_sms_enabled,
                    'via_telegram' => $event->is_telegram_enabled,
                    'via_site' => $event->is_site_enabled,
                ]);
            }

            switch ($action) {
                case 'enable_all':
                    $preference->via_email = $event->is_email_enabled ? true : $preference->via_email;
                    $preference->via_sms = $event->is_sms_enabled ? true : $preference->via_sms;
                    $preference->via_telegram = $event->is_telegram_enabled ? true : $preference->via_telegram;
                    $preference->via_site = $event->is_site_enabled ? true : $preference->via_site;
                    break;

                case 'disable_all':
                    $preference->via_email = false;
                    $preference->via_sms = false;
                    $preference->via_telegram = false;
                    $preference->via_site = false;
                    break;

                case 'enable_channel':
                    if ($event->isChannelEnabled($channel)) {
                        $preference->$channel = true;
                    }
                    break;

                case 'disable_channel':
                    $preference->$channel = false;
                    break;

                case 'reset_to_default':
                    $preference->via_email = $event->is_email_enabled;
                    $preference->via_sms = $event->is_sms_enabled;
                    $preference->via_telegram = $event->is_telegram_enabled;
                    $preference->via_site = $event->is_site_enabled;
                    break;
            }

            $preference->save();
            $updatedCount++;
        }

        return response()->json([
            'message' => 'Bulk update completed successfully',
            'updated_count' => $updatedCount,
            'action' => $action
        ], 200);
    }

    /**
     * ارسال اطلاع‌رسانی تستی (Test Notification)
     */
    public function testNotification(Request $request)
    {
        $user = auth('api')->user();
        
        $validData = Validator::make($request->all(), [
            'event_id' => ['required', 'exists:events,id'],
            'channels' => ['required', 'array'],
            'channels.*' => ['in:email,sms,site,telegram']
        ]);

        if (!$validData->passes()) {
            return response()->json(['message' => 'Error', 'errors' => $validData->errors()->toArray()], 422);
        }

        $event = Event::findOrFail($request->input('event_id'));
        $channels = $request->input('channels');
        
        // تبدیل channels به فرمت مورد نیاز
        $notificationChannels = [];
        foreach ($channels as $channel) {
            switch ($channel) {
                case 'email':
                    if ($event->is_email_enabled) {
                        $notificationChannels[] = 'mail';
                    }
                    break;
                case 'sms':
                    if ($event->is_sms_enabled) {
                        $notificationChannels[] = \App\Notifications\Channels\SmsChannel::class;
                    }
                    break;
                case 'site':
                    if ($event->is_site_enabled) {
                        $notificationChannels[] = 'database';
                    }
                    break;
                case 'telegram':
                    if ($event->is_telegram_enabled) {
                        // Telegram فعلا غیرفعال است
                        // $notificationChannels[] = 'telegram';
                    }
                    break;
            }
        }

        if (empty($notificationChannels)) {
            return response()->json([
                'message' => 'No valid channels available for this event',
                'available_channels' => [
                    'email' => $event->is_email_enabled,
                    'sms' => $event->is_sms_enabled,
                    'site' => $event->is_site_enabled,
                    'telegram' => $event->is_telegram_enabled
                ]
            ], 400);
        }

        try {
            $notificationData = [
                'event_id' => $event->id,
                'event_title' => $event->title,
                'event_slug' => $event->slug,
                'message' => 'این یک اطلاع‌رسانی تستی است. تنظیمات شما به درستی کار می‌کند.',
                'is_test' => true
            ];

            $user->notify(new \App\Notifications\CustomEventNotification($event, $notificationData, $notificationChannels));

            return response()->json([
                'message' => 'Test notification sent successfully',
                'channels' => $channels,
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error sending test notification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
