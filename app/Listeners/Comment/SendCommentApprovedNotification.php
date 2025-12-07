<?php

namespace App\Listeners\Comment;

use App\Events\Comment\CommentApproved;
use App\Notifications\Comment\CommentApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCommentApprovedNotification
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
     * @param  \App\Events\Comment\CommentApproved  $event
     * @return void
     */
    public function handle(CommentApproved $event)
    {
        if ($event->comment->user) {
            $event->comment->user->notify(
                new CommentApprovedNotification(
                    $event->comment,
                    $event->commentableTitle,
                    $event->commentableUrl
                )
            );
        }
    }
}
