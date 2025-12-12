<?php

namespace App\Listeners\Mission\Invitations;

use App\Events\Mission\InvitationEvent;
use Illuminate\Support\Facades\DB;

class SocialNetworkerListener
{
    protected $missionId = 'social-networker';

    /**
     * Handle the event.
     * ماموریت شبکه‌ساز اجتماعی: کاربری که لینک دعوت خود را به بیشترین تعداد شبکه‌های اجتماعی یا پلتفرم‌های مختلف به اشتراک گذاشته است.
     * (این نیاز به جدول جداگانه برای ردیابی اشتراک‌گذاری‌ها دارد - فعلاً بر اساس تعداد دعوت‌های موفق)
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

        // فرض: اگر کاربر بیش از 50 دعوت موفق داشته باشد، احتمالاً در شبکه‌های اجتماعی فعال است
        if ($invitedCount >= 50) {
            upgrade_mission_for_user($event->inviter->id, $this->missionId);
        }
    }
}
