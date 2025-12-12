<?php

namespace App\Listeners\Score\Payment;

use App\Events\Payment\VipUpgraded;
use App\Services\ScoresService;

class VipUpgradedListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(VipUpgraded $event)
    {
        $this->scoresService->awardScores(
            $event->user,
            "ارتقا به VIP: {$event->plan->title}",
            500
        );
    }
}

