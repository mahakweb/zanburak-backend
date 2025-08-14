<?php

namespace App\Http\Controllers\Auth;

// use App\Events\User\ChangePassword;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\MatchOldPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ChangePasswordController extends Controller
{

    public function update(Request $request){


        $validData = Validator::make($request->all(), [
            'old-password' => ['required', new MatchOldPassword],
            'new-password' => ['required', 'regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/', 'different:old-password']
        ]);

        if(!$validData->passes()){
            return response()->json(['status' => 0, 'error' => $validData->errors()->toArray()]);
        }else{
            $updateData = auth()->user()->update([
                'password' => Hash::make($request['new-password'])
            ]);
            if ($updateData){
                event(new PasswordReset(auth()->user()));
                return response()->json(['status' => 1, 'msg' => 'data has been successfully updated !']);
            }

        }


    }

}
