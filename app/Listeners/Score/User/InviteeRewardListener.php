<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\UserRegistered;
use App\Services\ScoresService;

class InviteeRewardListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    /**
     * Handle the event.
     * اعطای امتیاز به کاربر دعوت‌شده هنگام ثبت‌نام با کد دعوت
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

        $inviterName = $event->inviter->first_name . ' ' . $event->inviter->last_name;
        
        $this->scoresService->awardScores(
            $event->user,
            "ثبت‌نام با کد دعوت {$inviterName}",
            5000
        );
    }
}

