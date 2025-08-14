<?php

namespace App\Http\Controllers\Home;

use App\Events\FollowUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    public function store(Request $request){
        $followUser = User::find($request->followable_id);
        $auth = Auth::check();
        if(!$auth){
            return response()->json(['status' => 0, 'msg' => 'login first!'],200);
        }elseif(auth()->user()->id == $request->followable_id){
            return response()->json(['status' => 1, 'msg' => 'followable an follower same!'],200);
        }else{
            auth()->user()->toggleFollow($followUser);
            $hasFlollow = auth()->user()->isFollowing($followUser);
            if($hasFlollow){
                event(new FollowUser($followUser));
            }
            return response()->json(['status' => 2, 'hasFollow' => $hasFlollow, 'msg' => 'follow successfully!'], 200);
        }


    }
}
