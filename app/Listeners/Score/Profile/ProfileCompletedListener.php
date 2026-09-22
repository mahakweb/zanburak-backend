<?php

namespace App\Listeners\Score\Profile;

use App\Events\Score\Profile\ProfileCompleted;

class ProfileCompletedListener
{
    public function handle(ProfileCompleted $event)
    {
        upgrade_mission_for_user($event->user->id, 'profile-completed');
    }
}
