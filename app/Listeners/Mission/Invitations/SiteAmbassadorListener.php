<?php

namespace App\Listeners\Mission\Invitations;

use App\Events\Mission\InvitationEvent;
use Illuminate\Support\Facades\DB;

class SiteAmbassadorListener
{
    protected $missionId = 'site-ambassador';

    /**
     * Handle the event.
     * ماموریت سفیر سایت: کاربری که باعث دعوت حداقل 10 هزار کاربر به سایت شده است.
     *
     * @param  InvitationEvent  $event
     * @return void
     */
    public function handle(InvitationEvent $event)
    {
        $invitedCount = DB::table('invites')
            ->where('inviter_id', $event->inviter->id)
            ->where('invite_status', 'active')
            ->count();

        if ($invitedCount >= 10000) {
            upgrade_mission_for_user($event->inviter->id, $this->missionId);
        }
    }
}
