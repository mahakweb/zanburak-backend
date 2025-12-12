<?php

namespace App\Listeners\Score\Post;

use App\Events\Post\PostLiked;
use App\Services\ScoresService;

class PostLikedListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(PostLiked $event)
    {
        // فقط اگر لایک باشد (نه دیس‌لایک)
        if ($event->actionType !== 'لایک') {
            return;
        }

        // بررسی نوع محتوا از طریق URL
        $url = $event->actionUrl ?? '';
        
        // برای پاسخ‌ها
        if (str_contains($url, '/discuss/') || str_contains($url, '/answer')) {
            $this->scoresService->awardScores(
                $event->user,
                "دریافت لایک روی پاسخ: {$event->postTitle}",
                10
            );
        }
        // برای پرسش‌ها
        elseif (str_contains($url, '/question') || str_contains($url, '/discuss')) {
            $this->scoresService->awardScores(
                $event->user,
                "دریافت لایک روی پرسش: {$event->postTitle}",
                5
            );
        }
        // برای نظرات (episode comments)
        elseif (str_contains($url, '/episode/') || str_contains($url, '/comment')) {
            $this->scoresService->awardScores(
                $event->user,
                "دریافت لایک روی نظر: {$event->postTitle}",
                5
            );
        }
    }
}

