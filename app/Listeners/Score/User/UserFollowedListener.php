<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\UserFollowed;
use App\Services\ScoresService;

class UserFollowedListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(UserFollowed $event)
    {
        $this->scoresService->awardScores(
            $event->user,
            "دنبال کردن کاربر: {$event->followedUser->first_name} {$event->followedUser->last_name}",
            5
        );
    }
}

