<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\MobileVerified;
use App\Services\ScoresService;

class MobileVerifiedListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(MobileVerified $event)
    {
        $this->scoresService->awardScores(
            $event->user,
            'تایید شماره موبایل',
            50
        );
    }
}

