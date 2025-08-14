<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function toggleLike(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['Message' => 'Error! Login first'], 403);
        }
    
        $likeableType = ucfirst($request->input('likeable_type'));
        $likeableId = $request->input('likeable_id');
    
        if (!$likeableType || !$likeableId) {
            return response()->json(['Message' => 'Invalid likeable type or ID provided'], 400);
        }
    
        $className = "App\\Models\\" . $likeableType;
        if (!class_exists($className)) {
            return response()->json(['Message' => 'Invalid likeable type provided'], 400);
        }
    
        $modelInstance = $className::find($likeableId);
        if (!$modelInstance) {
            return response()->json(['Message' => 'Likeable object not found'], 404);
        }
    
        try {
            $user->toggleLike($modelInstance);
            $likesCount = $modelInstance->likes()->count();
            $userLiked = $user->hasLiked($modelInstance);
    
            return response()->json([
                'Message' => 'Success',
                'user_has_liked' => $userLiked,
                'likes_count' => $likesCount
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['Message' => 'An error occurred while toggling like', 'error' => $e->getMessage()], 500);
        }
    }
    
}
