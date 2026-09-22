<?php

namespace App\Listeners\Mission\Invitations;

use App\Events\Mission\InvitationEvent;
use Illuminate\Support\Facades\DB;

class SuccessfulInviterListener
{
    protected $missionId = 'successful-inviter';

    /**
     * Handle the event.
     * ماموریت جذب‌کننده موفق: کاربری که حداقل 1 کاربر دعوت‌شده توسط او در سایت ثبت‌نام کرده و فعالیت کرده‌اند. (سطح دارد)
     *
     * @param  InvitationEvent  $event
     * @return void
     */
    public function handle(InvitationEvent $event)
    {
        if (!$event->invitee) {
            return;
        }

        // بررسی اینکه آیا کاربر دعوت‌شده فعالیت داشته است (مثلاً خرید یا تکمیل دوره)
        $hasActivity = $event->invitee->courses()->count() > 0 || 
                       $event->invitee->questions()->count() > 0 ||
                       $event->invitee->answers()->count() > 0;

        if ($hasActivity) {
            // تعداد کاربران دعوت‌شده فعال
            $activeInviteesCount = DB::table('invites')
                ->join('users', 'invites.invitee_id', '=', 'users.id')
                ->where('invites.inviter_id', $event->inviter->id)
                ->where('invites.invite_status', 'active')
                ->where(function($query) {
                    $query->whereExists(function($q) {
                        $q->select(DB::raw(1))
                          ->from('course_user')
                          ->whereColumn('course_user.user_id', 'users.id');
                    })
                    ->orWhereExists(function($q) {
                        $q->select(DB::raw(1))
                          ->from('questions')
                          ->whereColumn('questions.user_id', 'users.id');
                    })
                    ->orWhereExists(function($q) {
                        $q->select(DB::raw(1))
                          ->from('answers')
                          ->whereColumn('answers.user_id', 'users.id');
                    });
                })
                ->count();

            sync_mission_progress_for_user($event->inviter->id, $this->missionId, (int) $activeInviteesCount);
        }
    }
}
