<?php

namespace App\Listeners\Mission\Invitations;

use App\Events\Mission\InvitationEvent;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContinuousInviterListener
{
    protected $missionId = 'continuous-inviter';

    /**
     * Handle the event.
     * ماموریت دعوت‌گر مستمر: کاربری که در یک بازه زمانی مشخص (مثلاً هر ماه) حداقل 20 کاربر جدید را با لینک دعوت به سایت جذب کرده است.
     *
     * @param  InvitationEvent  $event
     * @return void
     */
    public function handle(InvitationEvent $event)
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        // تعداد دعوت‌های این ماه
        $invitesThisMonth = DB::table('invites')
            ->where('inviter_id', $event->inviter->id)
            ->where('invite_status', 'active')
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<=', $monthEnd)
            ->count();

        if ($invitesThisMonth >= 20) {
            upgrade_mission_for_user($event->inviter->id, $this->missionId);
        }
    }
}
