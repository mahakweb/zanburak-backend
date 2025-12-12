<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\DailyLogin;
use App\Services\ScoresService;
use Illuminate\Support\Facades\Cache;

class DailyLoginListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(DailyLogin $event)
    {
        // بررسی اینکه آیا امروز قبلاً امتیاز ورود روزانه دریافت کرده است
        $cacheKey = "daily_login_scores_{$event->user->id}_" . now()->format('Y-m-d');
        
        if (Cache::has($cacheKey)) {
            return; // قبلاً امتیاز امروز را دریافت کرده
        }

        $this->scoresService->awardScores(
            $event->user,
            'ورود روزانه',
            10
        );

        // ذخیره در cache برای 24 ساعت
        Cache::put($cacheKey, true, now()->addDay());
    }
}

