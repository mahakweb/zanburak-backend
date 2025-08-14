<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function toggleBookmark(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['Message' => 'Error! Login first'], 403);
        }

        $bookmarkableType = ucfirst($request->input('bookmarkable_type'));
        $bookmarkableId = $request->input('bookmarkable_id');

        if (!$bookmarkableType || !$bookmarkableId) {
            return response()->json(['Message' => 'Invalid bookmarkable type or ID provided'], 400);
        }

        $className = "App\\Models\\" . $bookmarkableType;
        if (!class_exists($className)) {
            return response()->json(['Message' => 'Invalid bookmarkable type provided'], 400);
        }

        $modelInstance = $className::find($bookmarkableId);
        if (!$modelInstance) {
            return response()->json(['Message' => 'Bookmarkable object not found'], 404);
        }
        try {
            $user->toggleBookmark($modelInstance);
            $count = $modelInstance->bookmarkersCount();
            $bookmarked = $user->hasBookmarked($modelInstance);

            return response()->json([
                'Message' => 'Success',
                'bookmarked' => $bookmarked,
                'count' => $count
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['Message' => 'An error occurred while toggling bookmark', 'error' => $e->getMessage()], 500);
        }
    }

}
