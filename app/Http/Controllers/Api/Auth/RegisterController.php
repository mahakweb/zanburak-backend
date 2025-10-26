<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Info;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'mobile' => ['required', 'max:255', 'regex:/(09)[0-9]{9}/', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/', 'confirmed'],
            // 'terms' => ['required']
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user =  User::create([
            'first_name'  => $data['first_name'],
            'last_name'   => $data['last_name'],
            'email'       => $data['email'],
            'mobile'      => $data['mobile'],
            'username'    => $this->checkUsername($data['email']),
            'password'    => Hash::make($data['password']),
            'role'        => 'student',
            'profile_pic' => 'https://static.zanburak.ir/images/avatar/default.png',
            'cover_pic'   => 'https://static.zanburak.ir/images/cover/default.png',
            'last_seen'   => Carbon::now(),
        ]);


        return $user;
    }

    public function checkUsername($email){
        $username = Str::before($email, '@');
        $check = !! User::where('username', '=', $username)->first();
        return $check ? $username.'_'.time() : $username;
    }


    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        $user = $this->create($request->all());



        $this->guard()->login($user);

        event(new Registered($user));

        $user_info = Info::create([
            'user_id' => $user->id,
        ]);

        if ($response = $this->registered($request, $user)) {
            return $response;
        }

        // $token = $user->createToken('XSRF-TOKEN')->plainTextToken;
        $token = $user->createToken($request->userAgent());
        $user->tokens()->where('id', $token->accessToken->id)->update(['ip' => $request->ip()]);
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
            'last_seen' => $user->last_seen,
        ];
        return $request->wantsJson()
            ? new JsonResponse(['user' => $userData, 'token' => $token->plainTextToken], 201)
            : redirect($this->redirectPath());
    }
}
