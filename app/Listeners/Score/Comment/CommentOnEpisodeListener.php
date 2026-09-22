<?php

namespace App\Listeners\Score\Comment;

use App\Events\Score\Comment\CommentOnEpisode;

class CommentOnEpisodeListener
{
    public function handle(CommentOnEpisode $event)
    {
        $commentable = $event->comment->commentable;
        $title = $commentable->title ?? $commentable->subject ?? 'محتوا';

        award_mission_exp(
            $event->user,
            'comment-on-episode',
            "ثبت نظر روی: {$title}"
        );
        upgrade_mission_for_user($event->user->id, 'comment-on-episode', 1, 1, false);
    }
}
