<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\UserRegistered;

class InviterRewardListener
{
    public function handle(UserRegistered $event)
    {
        if (!$event->inviter) {
            return;
        }

        $inviteeName = trim(($event->user->first_name ?? '') . ' ' . ($event->user->last_name ?? ''));

        // امتیاز هر دعوت از levels ماموریت خوانده می‌شود (تکراری)
        award_mission_exp(
            $event->inviter,
            'inviter-reward',
            "دعوت کاربر {$inviteeName} به زنبورک"
        );
        upgrade_mission_for_user($event->inviter->id, 'inviter-reward', 1, 1, false);
    }
}
