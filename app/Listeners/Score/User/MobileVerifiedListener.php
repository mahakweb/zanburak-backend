<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\MobileVerified;

class MobileVerifiedListener
{
    public function handle(MobileVerified $event)
    {
        upgrade_mission_for_user($event->user->id, 'mobile-verified');
    }
}
