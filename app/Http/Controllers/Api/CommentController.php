<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Course;
use App\Models\Episode;
use App\Models\Path;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{

    // public function index(Request $request)
    // {
    //     $type = $request->input('type');
    //     $id = $request->input('id');


    //     $model = $this->getModelInstance($type, $id);
    //     if (!$model || !$this->isPublished($model)) {
    //         return response()->json(['error' => 'Not found'], 404);
    //     }

    //     $user = auth('api')->user();
    //     $perPage = $request->input('perPage', 10);
    //     $currentPage = $request->input('page', 1);

    //     $comments = $model->comments()
    //         ->select('id', 'user_id', 'comment', 'created_at', 'updated_at')
    //         ->where('parent_id', 0)
    //         ->where('approved', 1)
    //         ->orderBy('id', 'desc')
    //         ->with('user:id,first_name,last_name,username,profile_pic')
    //         ->get();

    //     $total = $comments->count();
    //     $lastPage = ceil($total / $perPage);

    //     $paginatedComments = $comments->slice(($currentPage - 1) * $perPage, $perPage)->values();

    //     $commentsWithDescendants = $paginatedComments->map(function ($comment) use ($user) {
    //         return $this->mapComment($comment, $user);
    //     });

    //     return response()->json([
    //         'message' => 'Success',
    //         'comments' => $commentsWithDescendants,
    //         'pagination' => [
    //             'total' => $total,
    //             'current_page' => (int) $currentPage,
    //             'per_page' => $perPage,
    //             'last_page' => $lastPage,
    //             'prev_page' => $currentPage > 1 ? $currentPage - 1 : null,
    //             'next_page' => $currentPage < $lastPage ? $currentPage + 1 : null,
    //         ]
    //     ], 200);
    // }

    public function index(Request $request)
    {
        $type = $request->input('type');
        $id = $request->input('id');

        $model = $this->getModelInstance($type, $id);
        if (!$model || !$this->isPublished($model)) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $user = auth('api')->user();
        $perPage = (int) $request->input('perPage', 10);

        $comments = $model->comments()
            ->select('id', 'user_id', 'comment', 'created_at', 'updated_at')
            ->where('parent_id', 0)
            ->where('approved', 1)
            ->orderBy('id', 'desc')
            ->with('user:id,first_name,last_name,username,profile_pic')
            ->paginate($perPage);

        $commentsWithDescendants = $comments->getCollection()->map(function ($comment) use ($user) {
            return $this->mapComment($comment, $user);
        });

        $comments->setCollection($commentsWithDescendants);

        return response()->json([
            'message' => 'Success',
            'comments' => $comments->items(),
            'pagination' => [
                'total' => $comments->total(),
                'current_page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'last_page' => $comments->lastPage(),
                'prev_page' => $comments->previousPageUrl(),
                'next_page' => $comments->nextPageUrl(),
            ]
        ], 200);
    }



    protected function mapComment($comment, $user)
    {
        $likesCount = $comment->likes()->count();
        $userHasLiked = $user ? $user->hasLiked($comment) : false;

        return [
            'id' => $comment->id,
            'user' => $comment->user ? $comment->user->only(['id', 'first_name', 'last_name', 'username', 'profile_pic']) : null,
            'comment' => $comment->comment,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
            'likes_count' => $likesCount,
            'user_has_liked' => $userHasLiked,
            'childs' => $this->getAllDescendants($comment, $user)
        ];
    }


    protected function getAllDescendants($comment, $user)
    {
        $allChilds = collect();

        $comment->load([
            'childs.user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'username', 'profile_pic');
            }
        ]);

        $descendants = $comment->childs->filter(function ($child) {
            return $child->approved == 1;
        });

        foreach ($descendants as $child) {
            $allChilds->push($this->mapComment($child, $user));
            $allChilds = $allChilds->merge($this->getAllDescendants($child, $user));
        }

        return $allChilds;
    }


    public function store(Request $request)
    {

        $type = $request->input('type');
        $id = $request->input('id');

        $user = auth('api')->user();
        $model = $this->getModelInstance($type, $id);

        if (!$model || !$this->isPublished($model)) {
            return response()->json(['message' => 'Model not found or unpublished.'], 404);
        }

        $validData = Validator::make($request->all(), [
            'comment' => ['required', 'min:10'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ]);
        if ($validData->fails()) {
            return response()->json(['message' => 'Fails', 'errors' => $validData->errors()], 422);
        }

        $validData = $validData->validated();
        $validData['parent_id'] = $validData['parent_id'] ?? 0;

        $comment = $model->comments()->create($validData + ['user_id' => $user->id]);

        // ارسال اطلاع‌رسانی در صورت پاسخ به کامنت
        if ($validData['parent_id'] > 0) {
            $parentComment = Comment::find($validData['parent_id']);
            if ($parentComment && $parentComment->user && $parentComment->user_id != $user->id) {
                $commentableTitle = $this->getCommentableTitle($parentComment);
                $commentableUrl = $this->getCommentableUrl($parentComment);
                
                event(new \App\Events\Comment\ReplyToComment($parentComment, $user, $commentableTitle, $commentableUrl));
            }
        }
        
        // ارسال اطلاع‌رسانی ثبت دیدگاه در مقالات/محتوا (اگر صاحب محتوا با کامنت‌کننده متفاوت باشد)
        if ($validData['parent_id'] == 0) {
            $commentable = $comment->commentable;
            if ($commentable && isset($commentable->user_id) && $commentable->user_id != $user->id) {
                $commentableTitle = $this->getCommentableTitle($comment);
                $commentableUrl = $this->getCommentableUrl($comment);
                
                event(new \App\Events\Comment\CommentOnArticle($comment, $user, $commentableTitle, $commentableUrl));
            }
            
            // Fire event for points if commentable is Episode
            if ($commentable instanceof \App\Models\Episode) {
                event(new \App\Events\Score\Comment\CommentOnEpisode($user, $comment));
            }
            
            // Fire Mission Community Activity Event
            event(new \App\Events\Mission\CommunityActivityEvent($user, 'comment', $comment));
        }

        $comment->load([
            'user' => function ($query) {
                $query->select('id', 'first_name', 'last_name', 'username', 'profile_pic');
            }
        ]);

        return response()->json(['message' => 'Success', 'comment' => $comment->only(['id', 'user_id', 'user', 'parent_id', 'comment', 'created_at', 'updated_at'])], 200);
    }



    private function getModelInstance($type, $id)
    {
        $modelClass = [
            'course' => Course::class,
            'episode' => Episode::class,
            'path' => Path::class,
        ][$type] ?? null;

        return $modelClass ? $modelClass::find($id) : null;
    }


    private function isPublished($model)
    {
        if (isset($model->publish)) {
            return $model->publish == 1;
        } elseif (isset($model->status)) {
            return $model->status == 1;
        }

        return false;
    }

    private function getCommentableTitle($comment)
    {
        $commentable = $comment->commentable;
        if (!$commentable) {
            return 'محتوا';
        }
        
        return $commentable->title ?? $commentable->subject ?? 'محتوا';
    }

    private function getCommentableUrl($comment)
    {
        $commentable = $comment->commentable;
        if (!$commentable) {
            return frontendUrl();
        }
        
        $slug = $commentable->slug ?? null;
        if (!$slug) {
            return frontendUrl();
        }
        
        // تعیین URL بر اساس نوع commentable
        if ($commentable instanceof Course) {
            return frontendUrl("course/{$slug}");
        } elseif ($commentable instanceof Episode) {
            return frontendUrl("course/{$commentable->section->course->slug}/episode/{$slug}");
        } elseif ($commentable instanceof Path) {
            return frontendUrl("path/{$slug}");
        }
        
        return frontendUrl();
    }
}
