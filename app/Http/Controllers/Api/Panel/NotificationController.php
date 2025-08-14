<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class NotificationController extends Controller
{
    public function notifications(Request $request)
    {
        $sortOrder = $request->input('sort', 'newest');
        $readStatus = $request->input('filter', 'all');
        $user = auth('api')->user();

        $query = match ($readStatus) {
            'read' => $user->notifications()->read(),
            'unread' => $user->notifications()->unread(),
            default => $user->notifications(),  // this method is override in user model
        };

        $query = match ($sortOrder) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $perPage = $request->input('perPage', 9);
        $currentPage = $request->input('page', 1);
        $total = $query->count();
        $lastPage = ceil($total / $perPage);

        $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
        $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

        $paginatedData = $query->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->select('id', 'data', 'read_at', 'created_at')
            ->get();

        return response()->json([
            'message' => 'success',
            'filter' => $readStatus,
            'notifications' => $paginatedData,
            'pagination' => [
                'total' => $total,
                'current_page' => intval($currentPage),
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage
            ]
        ], 200);
    }

    public function notificationDetails(Request $request)
    {
        $user = auth('api')->user();

        $notification = $user->notifications()->where('id', $request->id)->select('id', 'data', 'read_at', 'created_at')->first();

        if ($notification) {
            if (is_null($notification->read_at)) {
                $notification->read_at = now();
                $notification->save();
            }
            return response()->json(['message' => 'success', 'notification' => $notification], 200);

        }

        return response()->json(['message' => 'Error! Notification not found or unauthorized.'], 404);

    }

    public function deleteNotification(Request $request)
    {
        $user = auth('api')->user();
        $notification = $user->notifications()->where('id', $request->id)->first();
        if ($notification) {
            $notification->delete();
            return response()->json(['message' => 'Success! Notification has been deleted.'], 200);
        }
        return response()->json(['message' => 'Error! Notification not found or unauthorized.'], 404);
    }

    public function unreadNotifications(Request $request)
    {
        $user = auth('api')->user();
        $unreadNotifications = $user->notifications()->whereNull('read_at')->count();

        return response()->json(['message' => 'success', 'unread_count' => $unreadNotifications], 200);
    }

}
