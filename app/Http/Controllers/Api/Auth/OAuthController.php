<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLogin;
use Illuminate\Auth\Events\Login;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Info;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }
    protected $providers = [
        'github',
        'google',
    ];

    public function redirect($driver, Request $request)
    {
        $state = base64_encode(json_encode([
            'type' => $request->input('type', 'web'),
            'redirect' => $request->input('redirect', '/')
        ]));
        if (!$this->isProviderAllowed($driver)) {
            return "driver {$driver} is not currently supported";
        }
        try {
            return Socialite::driver($driver)->stateless()->with(['state' => $state, 'prompt' => 'consent select_account', 'access_type' => 'offline'])->redirect();
        } catch (\Exception $e) {
            // You should show something simple fail message
            return $e->getMessage();
        }
    }
    public function callback($driver, Request $request)
    {
        try {
            $user = Socialite::driver($driver)->stateless()->user();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        // check for email in returned user
        return empty($user->email)
            ? "No email id returned from {$driver} provider."
            : $this->loginOrCreateAccount($user, $driver, $request);
    }



    protected function loginOrCreateAccount($providerUser, $driver, $request)
    {
        // check for already has account
        $user = User::where('email', $providerUser->getEmail())->first();
        // if user already found
        if ($user) {
            // update the avatar and provider that might have changed
            // $user->update([
            //     'profile_pic' => $providerUser->avatar,
            //     'provider' => $driver,
            //     'provider_id' => $providerUser->id,
            //     'access_token' => $providerUser->token,
            // ]);
            $user->providers()->updateOrCreate(
                ['provider' => $driver],
                [
                    'provider_id' => $providerUser->getId(),
                    'access_token' => $providerUser->token,
                    'refresh_token' => $providerUser->refreshToken ?? null,
                    'expires_at' => isset($providerUser->expiresIn) ? now()->addSeconds($providerUser->expiresIn) : null,
                ]
            );
            event(new Login(false, $user, false));
        } else {
            // create a new user
            $first_name = '';
            if ($driver == 'github') {
                $first_name = $providerUser->getNickname();
            } elseif ($driver == 'google') {
                $first_name = $providerUser->getName();
            }
            $user = User::create([
                'first_name' => $first_name,
                'last_name' => ' ',
                'email' => $providerUser->getEmail(),
                'email_verified_at' => now(),
                'role' => 'student',
                // user can use reset password to create a password
                'password' => bcrypt(Str::random(16)),
                'username' => $this->checkUsername($providerUser->getEmail()),
                'profile_pic' => $providerUser->getAvatar(),
                'cover_pic' => 'https://static.zanburak.ir/images/cover/default.png',
                'last_seen' => Carbon::now(),

            ]);

            $user->providers()->create([
                'provider' => $driver,
                'provider_id' => $providerUser->getId(),
                'access_token' => $providerUser->token,
                'refresh_token' => $providerUser->refreshToken ?? null,
                'expires_at' => isset($providerUser->expiresIn) ? now()->addSeconds($providerUser->expiresIn) : null,
            ]);


            $userInfo = Info::create([
                'user_id' => $user->id,
            ]);

            event(new Registered($user));
        }
        // $token = $user->createToken('XSRF-TOKEN')->plainTextToken;
        $state = json_decode(base64_decode($request->input('state')), true);
        $requestType = $state['type'] ?? 'web';
        $redirect = $state['redirect'] ?? '/';
        $token = $user->createToken($request->userAgent());
        $user->tokens()->where('id', $token->accessToken->id)->update(['ip' => $request->ip()]);

        UserLogin::create([
            'user_id' => $user->id,
            'device' => $request->header('User-Agent') ?? 'unknown',
            'ip_address' => request()->ip(),
            'token_id' => $token->accessToken->id,
            'logged_in_at' => now(),
            'login_type' => $driver,
        ]);

        if ($requestType == 'web') {
            return redirect()->away(env("FRONT_APP_URL") . "/oauth/callback?token=" . $token->plainTextToken . "&redirect=" . $redirect);
        } else {
            return response()->json(['token' => $token->plainTextToken], 200);
        }
    }


    public function checkUsername($email)
    {
        $username = Str::before($email, '@');
        $check = !!User::where('username', '=', $username)->first();
        return $check ? $username . '_' . time() : $username;
    }


    private function isProviderAllowed($driver)
    {
        return in_array($driver, $this->providers) && config()->has("services.{$driver}");
    }
}
