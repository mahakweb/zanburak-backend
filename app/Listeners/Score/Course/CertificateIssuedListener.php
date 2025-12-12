<?php

namespace App\Listeners\Score\Course;

use App\Events\Course\CertificateIssued;
use App\Services\ScoresService;

class CertificateIssuedListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(CertificateIssued $event)
    {
        $this->scoresService->awardScores(
            $event->user,
            "دریافت گواهینامه دوره: {$event->course->title}",
            200
        );
    }
}

