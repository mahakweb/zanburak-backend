<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\UserRegistered;
use App\Services\ScoresService;

class NewUserRegistrationListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    /**
     * Handle the event.
     * اعطای امتیاز به کاربر جدید هنگام ثبت‌نام
     *
     * @param  UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        $this->scoresService->awardScores(
            $event->user,
            'پاداش ثبت‌نام در زنبورک',
            15000
        );
    }
}

