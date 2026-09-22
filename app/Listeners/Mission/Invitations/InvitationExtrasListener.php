<?php

namespace App\Listeners\Mission\Invitations;

use App\Events\Mission\InvitationEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvitationExtrasListener
{
    public function handle(InvitationEvent $event)
    {
        if (!$event->inviter) {
            return;
        }

        $inviterId = $event->inviter->id;

        if ($event->inviter->created_at && elapsed_hours($event->inviter->created_at, now()) <= 24) {
            $invitedCount = DB::table('invites')
                ->where('inviter_id', $inviterId)
                ->where('invite_status', 'active')
                ->count();

            if ($invitedCount >= 1) {
                upgrade_mission_for_user($inviterId, 'fast-inviter');
            }
        }

        $invitesToday = DB::table('invites')
            ->where('inviter_id', $inviterId)
            ->where('invite_status', 'active')
            ->where('created_at', '>=', Carbon::now()->startOfDay())
            ->count();

        if ($invitesToday >= 3) {
            upgrade_mission_for_user($inviterId, 'innovative-inviter');
        }
    }
}
