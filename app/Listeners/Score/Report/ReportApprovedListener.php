<?php

namespace App\Listeners\Score\Report;

use App\Events\Score\Report\ReportApprovedForScores;
use App\Services\ScoresService;

class ReportApprovedListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(ReportApprovedForScores $event)
    {
        $this->scoresService->awardScores(
            $event->user,
            "ارسال گزارش مفید: {$event->report->subject}",
            20
        );
    }
}

