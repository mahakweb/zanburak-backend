<?php

namespace App\Listeners\Mission\Invitations;

use App\Events\Mission\InvitationEvent;
use Illuminate\Support\Facades\DB;

class InfluentialInviterListener
{
    protected $missionId = 'influential-inviter';

    /**
     * Handle the event.
     * ماموریت دعوت‌گر تاثیرگذار: کاربری که حداقل 5 کاربر دعوت‌شده توسط او، ماموریت خریدار متعهد را کسب کرده باشند. (سطح دارد)
     *
     * @param  InvitationEvent  $event
     * @return void
     */
    public function handle(InvitationEvent $event)
    {
        // تعداد کاربران دعوت‌شده که ماموریت خریدار متعهد را دارند
        $influentialInviteesCount = DB::table('invites')
            ->join('mission_user', function($join) {
                $join->on('invites.invitee_id', '=', 'mission_user.user_id')
                     ->where('mission_user.mission_id', '=', 'loyal-buyer')
                     ->where('mission_user.progress', '>=', 5);
            })
            ->where('invites.inviter_id', $event->inviter->id)
            ->where('invites.invite_status', 'active')
            ->distinct()
            ->count('invites.invitee_id');

        sync_mission_progress_for_user($event->inviter->id, $this->missionId, (int) $influentialInviteesCount);
    }
}
