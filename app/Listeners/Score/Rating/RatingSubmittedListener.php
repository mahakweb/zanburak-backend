<?php

namespace App\Listeners\Score\Rating;

use App\Events\Score\Rating\RatingSubmitted;
use App\Services\ScoresService;

class RatingSubmittedListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(RatingSubmitted $event)
    {
        $rateable = $event->rating->rateable;
        $title = $rateable->title ?? $rateable->subject ?? 'محتوا';
        
        $this->scoresService->awardScores(
            $event->user,
            "ثبت امتیاز برای: {$title}",
            20
        );
    }
}

