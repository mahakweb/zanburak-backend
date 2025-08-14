<?php

namespace App\Http\Controllers\Api\Admin\comment;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    public function toggleApproval(Request $request)
    {
        $commet_id = $request->input('comment_id');
        $comment = Comment::find($commet_id);
        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        $comment->approved = !$comment->approved;
        $comment->save();

        $response = [
            'id' => $comment->id,
            'comment' => $comment,
            'approved' => $comment->approved,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at

        ];

        return response()->json(['message' => 'Success, comment updated successfully', 'comment' => $response], 200);
    }

    public function sendReply(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'comment' => ['required'],
            'parent_id' => ['required', 'exists:comments,id'],
            'parent_approved' => ['required', 'boolean'],
        ], [
            'parent_approved.required' => 'فیلد تایید کامنت والد اجباری است.',
            'parent_approved.boolean' => 'فیلد تایید کامنت والد باید true یا false باشد.',
        ]);

        if (!$validator->passes()) {
            return response()->json(['message' => 'Validation error!', 'errors' => $validator->errors()->toArray()], 422);
        } else {
            $validData = $validator->validated();
            $parent = Comment::find($request->parent_id);

            $reply = Comment::create([
                'user_id' => auth('api')->user()->id,
                'comment' => $validData['comment'],
                'approved' => true,
                'parent_id' => $parent->id,
                'commentable_id' => $parent->commentable_id,
                'commentable_type' => $parent->commentable_type
            ]);

            if ($request->parent_approved) {
                $parent->approved = true;
                $parent->save();
            }

            $response = [
                'id' => $reply->id,
                'comment' => $reply->comment,
                'approved' => $reply->approved,
                'created_at' => $reply->created_at,
                'parent_id' => $reply->parent_id,
                'user' => $reply->user->only('id', 'first_name', 'last_name', 'username', 'profile_pic'),
                'children' => [],
            ];

            return response()->json([
                'message' => 'success, your reply submited successfully.',
                'comment' => $response
            ], 200);
        }
    }

}
