<?php

namespace App\Listeners\Score\Course;

use App\Events\Course\CourseCompleted;
use App\Services\ScoresService;

class CourseCompletedListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(CourseCompleted $event)
    {
        $this->scoresService->awardScores(
            $event->user,
            "تکمیل دوره: {$event->course->title}",
            500
        );
    }
}

