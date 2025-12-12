<?php

namespace App\Listeners\Score\Episode;

use App\Events\Score\Episode\EpisodeFullyWatched;
use App\Services\ScoresService;

class EpisodeFullyWatchedListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(EpisodeFullyWatched $event)
    {
        $episode = $event->episode;
        $course = $episode->section->course;
        
        $this->scoresService->awardScores(
            $event->user,
            "مشاهده کامل اپیزود: {$episode->title} از دوره {$course->title}",
            25
        );
    }
}

