<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\UserRegistered;

class InviteeRewardListener
{
    public function handle(UserRegistered $event)
    {
        if (!$event->inviter) {
            return;
        }

        upgrade_mission_for_user($event->user->id, 'invitee-reward');
    }
}
