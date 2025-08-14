<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        'github', 'google',
    ];

    public function redirect($driver, Request $request)
    {
        $requestType = $request->input('type', 'web');
        if (!$this->isProviderAllowed($driver)) {
            return "driver {$driver} is not currently supported";
        }
        try {
            return Socialite::driver($driver)->stateless()->with(['state' => $requestType, 'prompt' => 'select_account'])->redirect();
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
            event(new Login(false, $user, false));
        } else {
            // create a new user
            $first_name = '';
            if($driver == 'github'){
                $first_name = $providerUser->getNickname();
            }
            elseif($driver == 'google'){
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
                'provider' => $driver,
                'provider_id' => $providerUser->getId(),
                'access_token' => $providerUser->token,
                'last_seen'   => Carbon::now(),

            ]);
            $userInfo = Info::create([
                'user_id' => $user->id,
            ]);

            event(new Registered($user));
        }
        // $token = $user->createToken('XSRF-TOKEN')->plainTextToken;
        $requestType = $request->input('state', 'web');
        $token = $user->createToken($request->userAgent());
        $user->tokens()->where('id', $token->accessToken->id)->update(['ip' => $request->ip()]);
        if ($requestType == 'web') {
            return redirect()->away(env("FRONT_APP_URL")."/oauth/callback?token=" . $token->plainTextToken);
        } else {
            return response()->json(['token' => $token->plainTextToken], 200);
        }
    }


    public function checkUsername($email){
        $username = Str::before($email, '@');
        $check = !! User::where('username', '=', $username)->first();
        return $check ? $username.'_'.time() : $username;
    }


    private function isProviderAllowed($driver)
    {
        return in_array($driver, $this->providers) && config()->has("services.{$driver}");
    }
}
