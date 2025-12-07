<?php

namespace App\Listeners\Comment;

use App\Events\Comment\CommentOnArticle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCommentOnArticleNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\Comment\CommentOnArticle  $event
     * @return void
     */
    public function handle(CommentOnArticle $event)
    {
        $commentable = $event->comment->commentable;
        if ($commentable && isset($commentable->user_id) && $commentable->user) {
            $commentable->user->notify(
                new \App\Notifications\Comment\CommentOnArticleNotification(
                    $event->comment,
                    $event->commenter,
                    $event->commentableTitle,
                    $event->commentableUrl
                )
            );
        }
    }
}
