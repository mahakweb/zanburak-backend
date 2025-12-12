<?php

namespace App\Listeners\Mission\Invitations;

use App\Events\Mission\InvitationEvent;
use Illuminate\Support\Facades\DB;

class NetworkBuilderListener
{
    protected $missionId = 'network-builder';

    /**
     * Handle the event.
     * ماموریت شبکه‌ساز: کاربری که حداقل 200 کاربر را با استفاده از لینک دعوت به سایت جذب کرده است. (سطح دارد)
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

        upgrade_mission_for_user($event->inviter->id, $this->missionId, $invitedCount, 1);
    }
}
