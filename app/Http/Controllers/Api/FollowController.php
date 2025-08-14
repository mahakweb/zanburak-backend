<?php

namespace App\Http\Controllers\Api;

use App\Events\FollowUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function toggleFollow(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['Message' => 'Error!  login first', 'errorType' => 'login'], 403);
        } else {
            $className = "App\\Models\\" . ucfirst($request->input('followable_type'));
            $model = new $className;
            $obj = $model::find($request->input('followable_id'));
            if ($obj) {
                if($className == 'App\Models\User' && $user->id == $obj->id) {
                    return response()->json(['Message' => 'Error!  You cannot follow yourself', 'errorType' => 'yourself'], 403);
                }
                $user->toggleFollow($obj);
                $hasFlollow = $user->isFollowing($obj);
                if ($className == 'App\Models\User') {
                    event(new FollowUser($obj));
                    return response()->json(['Message' => 'Success', 'hasFlollow' => $hasFlollow, 'numberOfFollowers' => $obj->followers->count(), 'numberOfFollowings' => $obj->followings->count()], 200);
                }
                return response()->json(['Message' => 'Success', 'hasFlollow' => $hasFlollow], 200);
            }
            return response()->json(['Message' => 'Error! Not found.'], 404);
        }

    }
}
