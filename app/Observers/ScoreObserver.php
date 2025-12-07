<?php

namespace App\Observers;

use App\Models\Score;
use Illuminate\Support\Facades\Cache;

class ScoreObserver
{
    /**
     * Handle the Score "created" event.
     *
     * @param  \App\Models\Score  $score
     * @return void
     */
    public function created(Score $score)
    {
        $user = $score->user;
        $totalScore = $user->currentScore();
        
        // Milestone های امتیاز (1000, 5000, 10000, 25000, 50000, 100000)
        $milestones = [1000, 5000, 10000, 25000, 50000, 100000];
        
        foreach ($milestones as $milestone) {
            // بررسی اینکه آیا کاربر به این milestone رسیده است
            if ($totalScore >= $milestone) {
                // بررسی اینکه آیا قبلاً برای این milestone notification ارسال شده است
                $notificationKey = "score_milestone_{$milestone}";
                $hasNotified = Cache::get("user_{$user->id}_{$notificationKey}");
                
                if (!$hasNotified) {
                    // ارسال notification
                    $user->notify(new \App\Notifications\Achievement\ScoreMilestoneNotification($milestone, $totalScore));
                    
                    // ذخیره در cache برای جلوگیری از ارسال مجدد
                    Cache::put("user_{$user->id}_{$notificationKey}", true, now()->addDays(1));
                }
            }
        }
    }
}

