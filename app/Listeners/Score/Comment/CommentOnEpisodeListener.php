<?php

namespace App\Listeners\Score\Comment;

use App\Events\Score\Comment\CommentOnEpisode;
use App\Services\ScoresService;

class CommentOnEpisodeListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(CommentOnEpisode $event)
    {
        $commentable = $event->comment->commentable;
        $title = $commentable->title ?? $commentable->subject ?? 'محتوا';
        
        $this->scoresService->awardScores(
            $event->user,
            "ثبت نظر روی: {$title}",
            30
        );
    }
}

