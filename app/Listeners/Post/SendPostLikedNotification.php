<?php

namespace App\Listeners\Post;

use App\Events\Post\PostLiked;

class SendPostLikedNotification
{
    public function handle(PostLiked $event)
    {
        $event->user->notify(
            new \App\Notifications\Post\LikeDislikePostNotification(
                $event->liker,
                $event->postTitle,
                $event->actionType,
                $event->actionUrl
            )
        );
    }
}
