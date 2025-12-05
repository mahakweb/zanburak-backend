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
            $wasLikedBefore = $user->hasLiked($modelInstance);
            $user->toggleLike($modelInstance);
            $isLikedNow = $user->hasLiked($modelInstance);
            $likesCount = $modelInstance->likes()->count();
            
            // ارسال اطلاع‌رسانی لایک/دیس‌لایک (فقط اگر لایک جدید اضافه شده باشد)
            if ($isLikedNow && !$wasLikedBefore && $modelInstance->user && $modelInstance->user_id != $user->id) {
                $likerName = $user->first_name . ' ' . $user->last_name;
                $postTitle = $this->getLikeableTitle($modelInstance);
                
                sendNotification($modelInstance->user, 'like-dislike-post', [
                    'message' => "{$likerName} مطلب شما «{$postTitle}» را لایک کرد.",
                    'subject' => 'لایک مطلب',
                    'action_url' => $this->getLikeableUrl($modelInstance),
                    'action_text' => 'مشاهده',
                    'sms_message' => "مطلب شما لایک شد.",
                ]);
            }
    
            return response()->json([
                'Message' => 'Success',
                'user_has_liked' => $isLikedNow,
                'likes_count' => $likesCount
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['Message' => 'An error occurred while toggling like', 'error' => $e->getMessage()], 500);
        }
    }
    
    private function getLikeableTitle($likeable)
    {
        if ($likeable instanceof \App\Models\Answer) {
            return $likeable->question->subject ?? 'پاسخ شما';
        } elseif ($likeable instanceof \App\Models\Question) {
            return $likeable->subject;
        } elseif ($likeable instanceof \App\Models\Comment) {
            $commentable = $likeable->commentable;
            return $commentable->title ?? $commentable->subject ?? 'دیدگاه شما';
        }
        return 'مطلب شما';
    }
    
    private function getLikeableUrl($likeable)
    {
        if ($likeable instanceof \App\Models\Answer) {
            return frontendUrl("discuss/{$likeable->question->slug}");
        } elseif ($likeable instanceof \App\Models\Question) {
            return frontendUrl("discuss/{$likeable->slug}");
        } elseif ($likeable instanceof \App\Models\Comment) {
            $commentable = $likeable->commentable;
            if (!$commentable) {
                return frontendUrl();
            }
            $slug = $commentable->slug ?? null;
            if (!$slug) {
                return frontendUrl();
            }
            if ($commentable instanceof \App\Models\Course) {
                return frontendUrl("course/{$slug}");
            } elseif ($commentable instanceof \App\Models\Episode) {
                return frontendUrl("course/{$commentable->section->course->slug}/episode/{$slug}");
            } elseif ($commentable instanceof \App\Models\Path) {
                return frontendUrl("path/{$slug}");
            }
        }
        return frontendUrl();
    }
    
}
