<?php

namespace App\Listeners\Score\User;

use App\Events\FollowUser;
use App\Events\Score\User\UserFollowed;
use App\Services\ScoresService;

class FollowUserListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(FollowUser $event)
    {
        $follower = auth()->user() ?? auth('api')->user();
        
        if ($follower && $follower->isFollowing($event->follower)) {
            // Fire UserFollowed event for points
            event(new UserFollowed($follower, $event->follower));
        }
    }
}

