<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\EmailVerified;
use App\Services\ScoresService;

class EmailVerifiedListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(EmailVerified $event)
    {
        $this->scoresService->awardScores(
            $event->user,
            'تایید آدرس ایمیل',
            50
        );
    }
}

