<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\EmailVerified;

class EmailVerifiedListener
{
    public function handle(EmailVerified $event)
    {
        upgrade_mission_for_user($event->user->id, 'email-verified');
    }
}
