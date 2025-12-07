<?php

namespace App\Http\Controllers\Api\Admin\Comment;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Get all comments with powerful filters (for admin panel).
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'all'); // all | course | episode | path
        $commentable_type = match ($filter) {
            'course' => \App\Models\Course::class,
            'episode' => \App\Models\Episode::class,
            'path' => \App\Models\Path::class,
            default => null, // 'all' or any other value means show all types
        };

        $sort = $request->input('sort', 'newest'); // newest | oldest
        $sortOrder = $sort === 'oldest' ? 'asc' : 'desc';

        $status = $request->input('status', 'all'); // all | published | unpublished
        $statusFilter = match ($status) {
            'published' => 1,
            'unpublished' => 0,
            default => null,
        };

        $viewMode = $request->input('viewMode', 'table'); // table | grid
        $withChildren = $request->input('with') === 'children' || $viewMode === 'grid';

        // User filter
        $userId = $request->input('user_id');
        $username = $request->input('username');

        // Search filter
        $search = $request->input('search');

        $commentsPerPage = (int) $request->input('perPage', 10);
        $currentPage = (int) $request->input('page', 1);

        if ($viewMode === 'grid' || $withChildren) {
            // Grid view: Get only parent comments with their children
            $commentsQuery = Comment::where('parent_id', 0)
                ->with(['commentable', 'user']);

            // Only filter by type if a specific type is selected
            if ($commentable_type !== null) {
                $commentsQuery->where('commentable_type', $commentable_type);
            }

            if (!is_null($statusFilter)) {
                $commentsQuery->where('approved', $statusFilter);
            }

            // User filter
            if ($userId) {
                $commentsQuery->where('user_id', $userId);
            } elseif ($username) {
                $commentsQuery->whereHas('user', function ($q) use ($username) {
                    $q->where('username', 'like', "%{$username}%");
                });
            }

            // Search filter
            if ($search) {
                $commentsQuery->where('comment', 'like', "%{$search}%");
            }

            $commentsQuery->orderBy('created_at', $sortOrder);

            $total = $commentsQuery->count();
            $lastPage = (int) ceil($total / ($commentsPerPage ?: 1));
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

            $paginatedComments = $commentsQuery->skip(($currentPage - 1) * $commentsPerPage)
                ->take($commentsPerPage)
                ->get();

            $result = $paginatedComments->map(function (Comment $item) use ($statusFilter, $sortOrder) {
                $commentable = $item->commentable;
                $commentable_type = $item->commentable_type;

                $base = [
                    'id' => $item->id,
                    'comment' => $item->comment,
                    'approved' => $item->approved,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'parent_id' => $item->parent_id,
                    'type' => class_basename($commentable_type),
                    'user' => $item->user
                        ? $item->user->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                        : null,
                ];

                // Load children
                $childrenQuery = $item->childs();
                if (!is_null($statusFilter)) {
                    $childrenQuery->where('approved', $statusFilter);
                }
                $children = $childrenQuery->orderBy('created_at', $sortOrder)
                    ->with(['user'])
                    ->get();

                $base['children'] = $children->map(function (Comment $child) {
                    return [
                        'id' => $child->id,
                        'comment' => $child->comment,
                        'approved' => $child->approved,
                        'created_at' => $child->created_at,
                        'updated_at' => $child->updated_at,
                        'parent_id' => $child->parent_id,
                        'user' => $child->user
                            ? $child->user->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                            : null,
                    ];
                })->values();

                // Commentable info
                $base['commentable'] = match ($commentable_type) {
                    \App\Models\Course::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'poster' => $commentable->poster,
                        'teacher' => $commentable->teacher
                            ? $commentable->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                            : null,
                    ] : null,
                    \App\Models\Episode::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'course' => $commentable->section && $commentable->section->course
                            ? array_merge(
                                $commentable->section->course->only('id', 'title', 'english_title', 'slug', 'poster'),
                                [
                                    'teacher' => $commentable->section->course->teacher
                                        ? $commentable->section->course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                                        : null,
                                ]
                            )
                            : null,
                    ] : null,
                    \App\Models\Path::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'poster' => $commentable->poster,
                        'icon' => $commentable->icon,
                    ] : null,
                    default => null,
                };

                return $base;
            })->values();
        } else {
            // Table view: Get all comments flat (including replies) sorted by time
            $commentsQuery = Comment::with(['commentable', 'parent.user', 'user']);

            // Only filter by type if a specific type is selected
            if ($commentable_type !== null) {
                $commentsQuery->where('commentable_type', $commentable_type);
            }

            if (!is_null($statusFilter)) {
                $commentsQuery->where('approved', $statusFilter);
            }

            // User filter
            if ($userId) {
                $commentsQuery->where('user_id', $userId);
            } elseif ($username) {
                $commentsQuery->whereHas('user', function ($q) use ($username) {
                    $q->where('username', 'like', "%{$username}%");
                });
            }

            // Search filter
            if ($search) {
                $commentsQuery->where('comment', 'like', "%{$search}%");
            }

            $commentsQuery->orderBy('created_at', $sortOrder);

            $total = $commentsQuery->count();
            $lastPage = (int) ceil($total / ($commentsPerPage ?: 1));
            $prevPage = $currentPage > 1 ? $currentPage - 1 : null;
            $nextPage = $currentPage < $lastPage ? $currentPage + 1 : null;

            $paginatedComments = $commentsQuery->skip(($currentPage - 1) * $commentsPerPage)
                ->take($commentsPerPage)
                ->get();

            $result = $paginatedComments->map(function (Comment $item) {
                $commentable = $item->commentable;
                $commentable_type = $item->commentable_type;

                $base = [
                    'id' => $item->id,
                    'comment' => $item->comment,
                    'approved' => $item->approved,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                    'parent_id' => $item->parent_id,
                    'type' => class_basename($commentable_type),
                    'user' => $item->user
                        ? $item->user->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                        : null,
                ];

                // Parent comment (if this is a reply)
                $base['parent'] = null;
                if ($item->relationLoaded('parent') && $item->parent) {
                    $base['parent'] = [
                        'id' => $item->parent->id,
                        'comment' => $item->parent->comment,
                        'approved' => $item->parent->approved,
                        'created_at' => $item->parent->created_at,
                        'user' => $item->parent->user
                            ? $item->parent->user->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                            : null,
                    ];
                }

                // Commentable info
                $base['commentable'] = match ($commentable_type) {
                    \App\Models\Course::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'poster' => $commentable->poster,
                        'teacher' => $commentable->teacher
                            ? $commentable->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                            : null,
                    ] : null,
                    \App\Models\Episode::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'course' => $commentable->section && $commentable->section->course
                            ? array_merge(
                                $commentable->section->course->only('id', 'title', 'english_title', 'slug', 'poster'),
                                [
                                    'teacher' => $commentable->section->course->teacher
                                        ? $commentable->section->course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                                        : null,
                                ]
                            )
                            : null,
                    ] : null,
                    \App\Models\Path::class => $commentable ? [
                        'id' => $commentable->id,
                        'title' => $commentable->title,
                        'english_title' => $commentable->english_title,
                        'slug' => $commentable->slug,
                        'poster' => $commentable->poster,
                        'icon' => $commentable->icon,
                    ] : null,
                    default => null,
                };

                return $base;
            })->values();
        }

        return response()->json([
            'message' => 'Success',
            'comments' => $result,
            'pagination' => [
                'total' => $total,
                'current_page' => $currentPage,
                'per_page' => $commentsPerPage,
                'last_page' => $lastPage,
                'prev_page' => $prevPage,
                'next_page' => $nextPage,
            ],
        ]);
    }

    public function toggleApproval(Request $request)
    {
        $commet_id = $request->input('comment_id');
        $comment = Comment::find($commet_id);
        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        
        $wasApproved = $comment->approved;
        $comment->approved = !$comment->approved;
        $comment->save();

        // ارسال اطلاع‌رسانی در صورت تایید کامنت - Notification فیزیکی خودش کانال‌ها را از NotificationService می‌گیرد
        if ($comment->approved && !$wasApproved && $comment->user) {
            $commentableTitle = $this->getCommentableTitle($comment);
            $commentableUrl = $this->getCommentableUrl($comment);
            
            $comment->user->notify(new \App\Notifications\Comment\CommentApprovedNotification($comment, $commentableTitle, $commentableUrl));
        }

        $response = [
            'id' => $comment->id,
            'comment' => $comment,
            'approved' => $comment->approved,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at

        ];

        return response()->json(['message' => 'Success, comment updated successfully', 'comment' => $response], 200);
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
        if ($commentable instanceof \App\Models\Course) {
            return frontendUrl("course/{$slug}");
        } elseif ($commentable instanceof \App\Models\Episode) {
            return frontendUrl("course/{$commentable->section->course->slug}/episode/{$slug}");
        } elseif ($commentable instanceof \App\Models\Path) {
            return frontendUrl("path/{$slug}");
        }
        
        return frontendUrl();
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

            // ارسال اطلاع‌رسانی به صاحب کامنت والد در صورت وجود
            if ($parent && $parent->user && $parent->user_id != auth('api')->user()->id) {
                $commentableTitle = $this->getCommentableTitle($parent);
                $replier = auth('api')->user();
                $replierName = $replier->first_name . ' ' . $replier->last_name;
                $commentableUrl = $this->getCommentableUrl($parent);
                
                // ارسال اطلاع‌رسانی - Notification فیزیکی خودش کانال‌ها را از NotificationService می‌گیرد
                $parent->user->notify(new \App\Notifications\Comment\ReplyToCommentNotification($parent, $replier, $commentableTitle, $commentableUrl));
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

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => ['required', 'exists:comments,id'],
            'comment' => ['required', 'min:10'],
        ]);

        if (!$validator->passes()) {
            return response()->json([
                'message' => 'Validation error!',
                'errors' => $validator->errors()->toArray()
            ], 422);
        }

        $comment = Comment::find($request->input('comment_id'));

        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        $comment->comment = $request->input('comment');
        $comment->save();

        $comment->load(['user', 'commentable', 'parent.user']);

        $commentable = $comment->commentable;
        $commentable_type = $comment->commentable_type;

        $response = [
            'id' => $comment->id,
            'comment' => $comment->comment,
            'approved' => $comment->approved,
            'created_at' => $comment->created_at,
            'updated_at' => $comment->updated_at,
            'parent_id' => $comment->parent_id,
            'type' => class_basename($commentable_type),
            'user' => $comment->user
                ? $comment->user->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                : null,
        ];

        // Parent comment (if this is a reply)
        $response['parent'] = null;
        if ($comment->parent) {
            $response['parent'] = [
                'id' => $comment->parent->id,
                'comment' => $comment->parent->comment,
                'approved' => $comment->parent->approved,
                'created_at' => $comment->parent->created_at,
                'user' => $comment->parent->user
                    ? $comment->parent->user->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                    : null,
            ];
        }

        // Commentable info
        $response['commentable'] = match ($commentable_type) {
            \App\Models\Course::class => $commentable ? [
                'id' => $commentable->id,
                'title' => $commentable->title,
                'english_title' => $commentable->english_title,
                'slug' => $commentable->slug,
                'poster' => $commentable->poster,
                'teacher' => $commentable->teacher
                    ? $commentable->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                    : null,
            ] : null,
            \App\Models\Episode::class => $commentable ? [
                'id' => $commentable->id,
                'title' => $commentable->title,
                'english_title' => $commentable->english_title,
                'slug' => $commentable->slug,
                'course' => $commentable->section && $commentable->section->course
                    ? array_merge(
                        $commentable->section->course->only('id', 'title', 'english_title', 'slug', 'poster'),
                        [
                            'teacher' => $commentable->section->course->teacher
                                ? $commentable->section->course->teacher->only('id', 'first_name', 'last_name', 'username', 'profile_pic')
                                : null,
                        ]
                    )
                    : null,
            ] : null,
            \App\Models\Path::class => $commentable ? [
                'id' => $commentable->id,
                'title' => $commentable->title,
                'english_title' => $commentable->english_title,
                'slug' => $commentable->slug,
                'poster' => $commentable->poster,
                'icon' => $commentable->icon,
            ] : null,
            default => null,
        };

        return response()->json([
            'message' => 'Success, comment updated successfully',
            'comment' => $response
        ], 200);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => ['required', 'exists:comments,id'],
        ]);

        if (!$validator->passes()) {
            return response()->json([
                'message' => 'Validation error!',
                'errors' => $validator->errors()->toArray()
            ], 422);
        }

        $comment = Comment::with('childs')->find($request->input('comment_id'));

        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        // حذف تمام زیرکامنت‌ها (به صورت بازگشتی)
        $comment->descendants(null)->each(function (Comment $child) {
            $child->delete();
        });

        $comment->delete();

        return response()->json([
            'message' => 'Success, comment deleted successfully'
        ], 200);
    }
}
