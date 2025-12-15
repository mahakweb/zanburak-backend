<?php

namespace App\Listeners\Score\User;

use App\Events\Score\User\DailyLogin;
use App\Services\ScoresService;
use Carbon\Carbon;
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
        $user = $event->user;
        $today = now()->format('Y-m-d');
        $cacheKey = "daily_login_processed_{$user->id}_" . $today;
        
        // بررسی Cache برای جلوگیری از پردازش مکرر در یک روز
        if (Cache::has($cacheKey)) {
            return; // قبلاً پردازش شده
        }
        
        // بررسی اینکه آیا امروز قبلاً امتیاز ورود روزانه دریافت کرده است
        $todayScore = $user->scores()
            ->where('description', 'like', '%ورود روزانه%')
            ->whereDate('created_at', $today)
            ->first();
        
        if ($todayScore) {
            // ذخیره در Cache برای جلوگیری از بررسی مجدد
            Cache::put($cacheKey, true, now()->addDay());
            return; // قبلاً امتیاز امروز را دریافت کرده
        }

        // محاسبه streak (روزهای متوالی ورود)
        $streak = $this->calculateStreak($user);
        
        // محاسبه امتیاز بر اساس streak
        $score = $this->calculateScoreByStreak($streak);
        
        // اعطای امتیاز ورود روزانه
        $description = $streak > 1 
            ? "ورود روزانه (روز {$streak} متوالی)" 
            : "ورود روزانه";
        
        $this->scoresService->awardScores(
            $user,
            $description,
            $score
        );

        // ثبت مأموریت ورود روزانه (اگر مأموریت با ID 'daily-login' وجود داشته باشد)
        $missionId = 'daily-login';
        $mission = \App\Models\Mission::find($missionId);
        if ($mission) {
            upgrade_mission_for_user($user->id, $missionId, 1, 1);
        }
        
        // ذخیره در Cache برای 24 ساعت تا دوباره پردازش نشود
        Cache::put($cacheKey, true, now()->addDay());
    }

    /**
     * محاسبه streak (روزهای متوالی ورود)
     * 
     * @param \App\Models\User $user
     * @return int
     */
    protected function calculateStreak($user)
    {
        // دریافت آخرین امتیاز ورود روزانه
        $lastLoginScore = $user->scores()
            ->where('description', 'like', '%ورود روزانه%')
            ->orderBy('created_at', 'desc')
            ->first();
        
        // اگر هیچ امتیازی وجود ندارد، این اولین ورود است
        if (!$lastLoginScore) {
            return 1;
        }
        
        $lastLoginDate = Carbon::parse($lastLoginScore->created_at);
        $yesterday = now()->subDay();
        
        // اگر آخرین ورود دیروز بوده، streak ادامه دارد
        if ($lastLoginDate->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            // محاسبه streak از آخرین امتیاز به عقب
            $streak = 1; // امروز
            $expectedDate = $yesterday->copy();
            
            // دریافت تمام امتیازهای ورود روزانه به ترتیب نزولی
            $dailyLoginScores = $user->scores()
                ->where('description', 'like', '%ورود روزانه%')
                ->orderBy('created_at', 'desc')
                ->get();
            
            foreach ($dailyLoginScores as $score) {
                $scoreDate = Carbon::parse($score->created_at)->format('Y-m-d');
                $expectedDateFormatted = $expectedDate->format('Y-m-d');
                
                // اگر تاریخ امتیاز با تاریخ مورد انتظار مطابقت دارد، streak ادامه دارد
                if ($scoreDate === $expectedDateFormatted) {
                    $streak++;
                    $expectedDate->subDay();
                } else {
                    // اگر تاریخ مطابقت ندارد، streak می‌شکند
                    break;
                }
            }
            
            return $streak;
        }
        
        // اگر آخرین ورود بیشتر از یک روز گذشته، streak می‌شکند و از 1 شروع می‌شود
        return 1;
    }

    /**
     * محاسبه امتیاز بر اساس streak
     * 
     * @param int $streak
     * @return int
     */
    protected function calculateScoreByStreak($streak)
    {
        // روز اول: 100 امتیاز
        if ($streak == 1) {
            return 100;
        }
        
        // روز دوم متوالی: 15 امتیاز
        if ($streak == 2) {
            return 15;
        }
        
        // روز سوم و بیشتر: 200 امتیاز
        return 200;
    }
}

