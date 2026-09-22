<?php

namespace App\Listeners\Score\Comment;

use App\Events\Comment\ReplyToComment;

class ReplyToCommentListener
{
    public function handle(ReplyToComment $event)
    {
        $commentable = $event->comment->commentable;
        $title = $commentable->title ?? $commentable->subject ?? 'محتوا';

        award_mission_exp(
            $event->replier,
            'reply-to-comment',
            "پاسخ به نظر روی: {$title}"
        );
        upgrade_mission_for_user($event->replier->id, 'reply-to-comment', 1, 1, false);
    }
}
