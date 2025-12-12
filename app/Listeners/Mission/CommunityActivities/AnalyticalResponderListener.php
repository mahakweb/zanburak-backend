<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;

class AnalyticalResponderListener
{
    protected $missionId = 'analytical-responder';

    /**
     * Handle the event.
     * ماموریت تحلیل‌گر پرسش: کاربری که به پرسش‌ها پاسخ‌های جامع و با تحلیل عمیق ارائه کرده است.
     * (پاسخ‌هایی با طول بیش از 500 کاراکتر که لایک گرفته‌اند)
     *
     * @param  CommunityActivityEvent  $event
     * @return void
     */
    public function handle(CommunityActivityEvent $event)
    {
        if ($event->type !== 'answer' || !$event->answer) {
            return;
        }

        $answer = $event->answer;
        
        // بررسی اینکه آیا پاسخ جامع است (بیش از 500 کاراکتر) و لایک دارد
        $isAnalytical = strlen($answer->answer) > 500 && 
                       $answer->likes()->where('type', 'like')->count() > 0;

        if ($isAnalytical) {
            // تعداد پاسخ‌های تحلیلی کاربر
            $analyticalAnswersCount = $event->user->answers()
                ->whereRaw('LENGTH(answer) > 500')
                ->whereHas('likes', function($q) {
                    $q->where('type', 'like');
                })
                ->count();

            upgrade_mission_for_user($event->user->id, $this->missionId, $analyticalAnswersCount, 1);
        }
    }
}
