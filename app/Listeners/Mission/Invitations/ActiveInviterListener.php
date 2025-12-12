<?php

namespace App\Listeners\Mission\Invitations;

use App\Events\Mission\InvitationEvent;
use Illuminate\Support\Facades\DB;

class ActiveInviterListener
{
    protected $missionId = 'active-inviter';

    /**
     * Handle the event.
     * ماموریت دعوت‌گر فعال: کاربری که حداقل 1 کاربر را با استفاده از لینک دعوت به سایت جذب کرده است. (حداکثر 200 کاربر دعوتی) (سطح دارد)
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

        // حداکثر 200 کاربر
        $invitedCount = min($invitedCount, 200);

        upgrade_mission_for_user($event->inviter->id, $this->missionId, $invitedCount, 1);
    }
}
