<?php

namespace App\Listeners\Score\Comment;

use App\Events\Comment\ReplyToComment;
use App\Services\ScoresService;

class ReplyToCommentListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(ReplyToComment $event)
    {
        $commentable = $event->comment->commentable;
        $title = $commentable->title ?? $commentable->subject ?? 'محتوا';
        
        $this->scoresService->awardScores(
            $event->replier,
            "پاسخ به نظر روی: {$title}",
            15
        );
    }
}

