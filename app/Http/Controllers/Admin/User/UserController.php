<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::orderBy('id', 'desc')->get();
        return view('admin.apps.user-management.users.list', compact('users'));
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
        return view('admin.apps.user-management.users.view', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        


            $profile = $request->file('profile_pic');
            $destinationPath = '/assets/images/users/profile/'.now()->year.'/'.now()->month.'/'.now()->day.'/';
            $profile->move(public_path($destinationPath), time().'-'.$profile->getClientOriginalName());
            $profile_pic = $profile->getClientOriginalName();

            // $validData = Validator::make($request->all(), [
            //     'comment_id' => ['required'],
            //     'comment_status' => ['required'],
            // ]);

            // if(!$validData->passes()){
                // return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
            // }else{
                // $comment = Comment::findOrFail($request['comment_id']);
                // $status = $comment->update([
                    // 'approved' => $request['comment_status']
                // ]);
                // if($status){
                    return response()->json(['status' => 1, 'msg' => $profile_pic]);
                // }
            // }
        
    }



    public function updateEmail(Request $request){
        
            $validData = Validator::make($request->all(), [
                'email' => ['email'],
            ]);
            if(!$validData->passes()){
                return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
            }else{
                $user = User::findOrFail($request['user_id']);
                $update = $user->update([
                    'email' => $request['email'],
                    'email_verified_at' => null
                ]);
                if($update){
                    return response()->json(['status' => 1, 'msg' => $request['email']]);
                }

            }
        
    }


    public function updatePassword(Request $request){
        

            $user = User::findOrFail($request['user_id']);

            $validData = Validator::make($request->all(), [
                'new_password' => ['required', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/']
            ]);

            if(!$validData->passes()){
                return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
            }else{
                $updateData = $user->update([
                    'password' => Hash::make($request['new_password'])
                ]);
                if ($updateData){
                    return response()->json(['status' => 1, 'msg' => 'data has been successfully updated !']);
                }

            }
        
    }

    public function updateRoles(Request $request, User $user){
        

            $validData = Validator::make($request->all(), [
                'roles' => ['array']
            ]);

            if(!$validData->passes()){
                return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
            }else{
                $updateData = $user->roles()->sync($request['roles']);
                if ($updateData){
                    return response()->json(['status' => 1, 'msg' => 'data has been successfully updated !']);
                }

            }
        
    }

    public function updatePermissions(Request $request, User $user){
        

            $validData = Validator::make($request->all(), [
                'permissions' => ['array']
            ]);

            if(!$validData->passes()){
                return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
            }else{
                $updateData = $user->permissions()->sync($request['permissions']);
                if ($updateData){
                    return response()->json(['status' => 1, 'msg' => 'data has been successfully updated !']);
                }

            }
        
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
