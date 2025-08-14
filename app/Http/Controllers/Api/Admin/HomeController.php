<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function searchUser(Request $request)
    {
        $user = auth('api')->user();
        $searchKey = $request->input('key');
        $limit = $request->input('limit', 20);

        if (!$searchKey) {
            return response()->json([
                'message' => 'No search key provided',
                'result' => [],
            ], 200);
        }


        $users = User::query()
            ->where('first_name', 'LIKE', "%{$searchKey}%")
            ->orWhere('last_name', 'LIKE', "%{$searchKey}%")
            ->orWhere('username', 'LIKE', "%{$searchKey}%")
            ->orWhere('email', 'LIKE', "%{$searchKey}%")
            ->limit(value: $limit)
            ->get(['id', 'first_name', 'last_name', 'username', 'email', 'profile_pic', 'cover_pic']);

        return response()->json([
            'message' => 'Success',
            'result' => $users,
        ], 200);
    }
}
