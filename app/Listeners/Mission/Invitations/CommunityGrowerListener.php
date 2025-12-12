<?php

namespace App\Listeners\Mission\Invitations;

use App\Events\Mission\InvitationEvent;
use Illuminate\Support\Facades\DB;

class CommunityGrowerListener
{
    protected $missionId = 'community-grower';

    /**
     * Handle the event.
     * ماموریت رشد‌دهنده جامعه: کاربری که لینک دعوتش باعث دعوت حداقل 200 کاربر فعال شده است.
     *
     * @param  InvitationEvent  $event
     * @return void
     */
    public function handle(InvitationEvent $event)
    {
        // تعداد کاربران دعوت‌شده فعال (که خودشان هم دعوت کرده‌اند)
        $activeInviteesCount = DB::table('invites as i1')
            ->join('invites as i2', 'i1.invitee_id', '=', 'i2.inviter_id')
            ->where('i1.inviter_id', $event->inviter->id)
            ->where('i1.invite_status', 'active')
            ->where('i2.invite_status', 'active')
            ->distinct()
            ->count('i1.invitee_id');

        if ($activeInviteesCount >= 200) {
            upgrade_mission_for_user($event->inviter->id, $this->missionId);
        }
    }
}
