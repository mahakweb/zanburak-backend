<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\DailyLogin;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DailyLoginListener
{
    public function handle(DailyLogin $event)
    {
        $user = $event->user;
        $today = now()->format('Y-m-d');
        $cacheKey = "daily_login_processed_{$user->id}_" . $today;

        if (Cache::has($cacheKey)) {
            return;
        }

        $todayScore = $user->scores()
            ->where('description', 'like', '%ورود روزانه%')
            ->whereDate('created_at', $today)
            ->first();

        if ($todayScore) {
            Cache::put($cacheKey, true, now()->addDay());
            return;
        }

        $streak = $this->calculateStreak($user);
        $level = $streak === 1 ? 1 : ($streak === 2 ? 2 : 3);
        $description = $streak > 1
            ? "ورود روزانه (روز {$streak} متوالی)"
            : "ورود روزانه";

        // امتیاز از levels ماموریت daily-login (سطح ۱/۲/۳)
        award_mission_exp($user, 'daily-login', $description, $level);
        upgrade_mission_for_user($user->id, 'daily-login', 1, 1, false);

        Cache::put($cacheKey, true, now()->addDay());
    }

    protected function calculateStreak($user): int
    {
        $lastLoginScore = $user->scores()
            ->where('description', 'like', '%ورود روزانه%')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastLoginScore) {
            return 1;
        }

        $lastLoginDate = Carbon::parse($lastLoginScore->created_at);
        $yesterday = now()->subDay();

        if ($lastLoginDate->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            $streak = 1;
            $expectedDate = $yesterday->copy();

            $dailyLoginScores = $user->scores()
                ->where('description', 'like', '%ورود روزانه%')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($dailyLoginScores as $score) {
                $scoreDate = Carbon::parse($score->created_at)->format('Y-m-d');
                if ($scoreDate === $expectedDate->format('Y-m-d')) {
                    $streak++;
                    $expectedDate->subDay();
                } else {
                    break;
                }
            }

            return $streak;
        }

        return 1;
    }
}
