<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\UserRegistered;

class NewUserRegistrationListener
{
    public function handle(UserRegistered $event)
    {
        upgrade_mission_for_user($event->user->id, 'user-registration');
    }
}
