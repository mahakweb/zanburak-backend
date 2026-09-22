<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\UserFollowed;

class UserFollowedListener
{
    public function handle(UserFollowed $event)
    {
        $name = trim(($event->followedUser->first_name ?? '') . ' ' . ($event->followedUser->last_name ?? ''));

        award_mission_exp(
            $event->user,
            'user-followed',
            "دنبال کردن کاربر: {$name}"
        );
        upgrade_mission_for_user($event->user->id, 'user-followed', 1, 1, false);
    }
}
