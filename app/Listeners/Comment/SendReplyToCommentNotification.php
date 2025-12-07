<?php

namespace App\Listeners\Comment;

use App\Events\Comment\ReplyToComment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendReplyToCommentNotification
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
     * @param  \App\Events\Comment\ReplyToComment  $event
     * @return void
     */
    public function handle(ReplyToComment $event)
    {
        if ($event->comment->user) {
            $event->comment->user->notify(
                new \App\Notifications\Comment\ReplyToCommentNotification(
                    $event->comment,
                    $event->replier,
                    $event->commentableTitle,
                    $event->commentableUrl
                )
            );
        }
    }
}
