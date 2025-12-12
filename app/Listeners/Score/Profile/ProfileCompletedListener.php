<?php

namespace App\Listeners\Score\Profile;

use App\Events\Score\Profile\ProfileCompleted;
use App\Services\ScoresService;

class ProfileCompletedListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(ProfileCompleted $event)
    {
        $this->scoresService->awardScores(
            $event->user,
            'تکمیل پروفایل',
            100
        );
    }
}

