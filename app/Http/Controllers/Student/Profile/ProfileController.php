<?php

namespace App\Http\Controllers\Student\Profile;

use App\Events\User\ChangeMobile;
use App\Http\Controllers\Controller;
use App\Models\ActiveCode;
use App\Models\User;
use App\Models\Session;
use App\Notifications\ActiveCodeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Rules\CheckCurrentMobile;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        return view('student.profile.index');
    }


    public function updateMobile(Request $request){
        // if($request->ajax()) {
            $validData = Validator::make($request->all(), [
                // 'current_mobile' => ['required', 'exists:users,mobile'],
                'mobile' => ['required', 'regex:/(09)[0-9]{9}/', Rule::unique('users')->ignore($request->user()->id), new CheckCurrentMobile],
            ]);

            if(!$validData->passes()){
                return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
            }else{
                //TODO create and send code
                $code = ActiveCode::genereteCode($request['mobile']);

                if(!$request->session()->has('last_send_notif') || now()->diffInSeconds($request->session()->get('last_send_notif')) > 120){

                    $request->user()->notify(new ActiveCodeNotification($code, $request['mobile']));

                    $request->session()->put('last_send_notif', now());

                }


                $request->session()->flash('user_phone', [
                    'phone' => $request['mobile']
                ]);

                return response()->json(['status' => 1, 'user_phone' => $request['mobile'], 'msg' => 'successfully']);
            }
        // }
    }

    public function verifyToken(Request $request){
        // if($request->ajax()) {
            $validator = Validator::make($request->all(), [
                'code' => ['required', 'numeric', 'min:6'],
            ]);

            if(!$validator->passes()){
                $request->session()->keep(['user_phone']);
                $request->session()->keep(['last_send_notif']);
                return response()->json(['status' => 0, 'error' => $validator->errors()->toArray()]);
            }else{
                $validData = $validator->validated();
                $phone = $request->session()->get('user_phone.phone');

                $status = ActiveCode::verifyCode($phone, $validData['code']);

                if (!$status){
                    $request->session()->keep(['user_phone']);
                    $request->session()->keep(['last_send_notif']);
                    return response()->json(['status' => 0, 'error' => ['code' => ['کد وارد شده صحیح نیست.']]]);
                }

                ActiveCode::where('user_phone', $phone)->delete();

                $update = auth()->user()->update([
                    'mobile' => $phone,
                    'mobile_verified_at' => now()
                ]);

                $request->session()->forget('user_phone.phone');
                $request->session()->forget('last_send_notif');


                event(new ChangeMobile());

                return response()->json(['status' => 1, 'new_mobile' => $phone, 'msg' => 'successfully']);
            }
        // }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        // if($request->ajax()){
            $validData = Validator::make($request->all(), [
                'first_name' => ['required', 'min:3'],
                'last_name' => ['required', 'min:3'],
                'username' => ['required', Rule::unique('users', 'username')->ignore($request->user()->id)],
                'birth-date' => ['required', 'regex:/^[1-4]\d{3}\/((0[1-6]\/((3[0-1])|([1-2][0-9])|(0[1-9])))|((1[0-2]|(0[7-9]))\/(30|31|([1-2][0-9])|(0[1-9]))))$/'],
                'job' => ['required','min:3'],
                'about' => ['required', 'min:10'],
            ]);


            if(!$validData->passes()){
                return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
            }else{
                $updateUser = auth()->user()->update([
                    'first_name' => $request['first_name'],
                    'last_name' => $request['last_name'],
                    'username' => $request['username'],
                ]);
                $updateInfo = auth()->user()->info->update([
                    'birth_date' => $request['birth-date'],
                    'job' => $request['job'],
                    'about' =>$request['about'],
                    'website' =>$request['website'],
                    'github' =>$request['github'],
                    'linkedin' =>$request['linkedin'],
                    'telegram' =>$request['telegram'],
                    'instagram' =>$request['instagram'],
                    'twitter' =>$request['twitter'],

                ]);
                if ($updateUser && $updateInfo){
                    return response()->json(['status' => 1, 'msg' => 'data has been successfully updated !']);
                }

            }
        // }
    }


    public function updateProfilePic(Request $request){
        $validData = Validator::make($request->all(), [
            'profile' => ['required', 'mimetypes:image/png,image/jpg,image/jpeg', 'max:2048'],
        ], [
            'profile.mimetypes' => 'تصویر مورد نظر باید یکی از فرمت‌های: png, jpg, jpeg باشد',
        ]);

        if( ! $validData->passes() ){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else{

            $prev_pic = parse_url(auth()->user()->profile_pic)['path'];

            $storagePath  = Storage::disk('static')->url('');

            $path = Storage::disk('static')->put('/images/avatar/'.now()->year.'/'.now()->month.'/'.now()->day, $request->profile);

            if(auth()->user()->profile_pic != '/assets/images/users/profile/default-user.png' && Storage::disk('static')->exists($prev_pic)){
                Storage::disk('static')->delete($prev_pic);
            }
            auth()->user()->update([
                'profile_pic' => $storagePath.$path
            ]);

            return response()->json(['status' => 1, 'msg' => 'success']);
        }
    }

    public function updateCoverPic(Request $request){
        $validData = Validator::make($request->all(), [
            'cover' => ['required', 'mimetypes:image/png,image/jpg,image/jpeg', 'max:2048'],
        ], [
            'cover.mimetypes' => 'تصویر مورد نظر باید یکی از فرمت‌های: png, jpg, jpeg باشد',
        ]);

        if( ! $validData->passes() ){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else{

            $prev_pic = parse_url(auth()->user()->cover_pic)['path'];

            $storagePath  = Storage::disk('static')->url('');

            $path = Storage::disk('static')->put('/images/cover/'.now()->year.'/'.now()->month.'/'.now()->day, $request->cover);

            if(auth()->user()->cover_pic != '/assets/images/users/cover/cover-default.webp' && Storage::disk('static')->exists($prev_pic)){
                Storage::disk('static')->delete($prev_pic);
            }
            auth()->user()->update([
                'cover_pic' => $storagePath.$path
            ]);

            return response()->json(['status' => 1, 'msg' => 'success']);
        }
    }


    public function terminateSession(Request $request){

        // if($request->ajax()) {
            $validData = Validator::make($request->all(), [
                'id' => ['required', 'exists:sessions,id']
            ]);
            if (!$validData->passes()) {
                return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
            } else {
                $session = auth()->user()->sessions()->where('id', $request['id'])->first();

                $currentSession = Session::find($request->session()->getId());

                if($currentSession->created_at > $session->created_at){
                    if(Carbon::now()->diffInHours($currentSession->created_at) < 12){
                        return response()->json(['status' => 2, 'msg' => 'login time less of 12 hour']);
                    }
                }

                $request->user()->update([
                    'remember_token' => null,
                ]);

                $session->delete();

                return response()->json(['status' => 1, 'msg' => 'successfully']);

            }
        // }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
}
