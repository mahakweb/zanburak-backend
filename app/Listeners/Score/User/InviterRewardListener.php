<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\UserRegistered;
use App\Services\ScoresService;

class InviterRewardListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    /**
     * Handle the event.
     * اعطای امتیاز به دعوت‌کننده هنگام ثبت‌نام کاربر جدید با کد دعوت
     *
     * @param  UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        // فقط اگر کاربر با کد دعوت ثبت‌نام کرده باشد
        if (!$event->inviter) {
            return;
        }

        $inviteeName = $event->user->first_name . ' ' . $event->user->last_name;
        
        $this->scoresService->awardScores(
            $event->inviter,
            "دعوت کاربر {$inviteeName} به زنبورک",
            10000
        );
    }
}

