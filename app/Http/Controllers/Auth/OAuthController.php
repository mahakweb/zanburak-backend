<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Info;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }
    protected $providers = [
        'github', 'google',
    ];

    public function redirect($driver)
    {
        if (!$this->isProviderAllowed($driver)) {
            return $this->sendFailedResponse("driver {$driver} is not currently supported");
        }
        try {
            return Socialite::driver($driver)->redirect();
        } catch (\Exception $e) {
            // You should show something simple fail message
            return $this->sendFailedResponse($e->getMessage());
        }
    }
    public function callback($driver)
    {
        try {
            $user = Socialite::driver($driver)->user();
        } catch (\Exception $e) {
            return $this->sendFailedResponse($e->getMessage());
        }
        // check for email in returned user
        return empty($user->email)
        ? $this->sendFailedResponse("No email id returned from {$driver} provider.")
        : $this->loginOrCreateAccount($user, $driver);
    }
    protected function sendSuccessResponse()
    {
        return redirect()->intended();
    }
    protected function sendFailedResponse($msg = null)
    {
        return redirect()->route('login')
            ->withErrors(['msg' => $msg ?: 'Unable to login, try with another provider to login.']);
    }
    protected function loginOrCreateAccount($providerUser, $driver)
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
                'role' => 'student',
                // user can use reset password to create a password
                'password' => bcrypt(Str::random(16)),
                'username' => Str::before($providerUser->getEmail(), '@'),
                'profile_pic' => $providerUser->getAvatar(),
                'cover_pic' => '/assets/images/users/cover/cover-default.webp',
                'provider' => $driver,
                'provider_id' => $providerUser->getId(),
                'access_token' => $providerUser->token,

            ]);
            $userInfo = Info::create([
                'user_id' => $user->id,
            ]);

            event(new Registered($user));
        }
        auth()->loginUsingId($user->id);
        return $this->sendSuccessResponse();
    }
    private function isProviderAllowed($driver)
    {
        return in_array($driver, $this->providers) && config()->has("services.{$driver}");
    }
}
