<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;

class ProblemSolverListener
{
    protected $missionId = 'problem-solver';

    /**
     * Handle the event.
     * ماموریت حل‌کننده مشکلات: کاربری که حداقل 1 پاسخش توسط پرسشگران به عنوان بهترین پاسخ انتخاب شده است. (سطح دارد)
     *
     * @param  CommunityActivityEvent  $event
     * @return void
     */
    public function handle(CommunityActivityEvent $event)
    {
        if ($event->type !== 'answer') {
            return;
        }

        // تعداد پاسخ‌هایی که به عنوان بهترین پاسخ انتخاب شده‌اند
        $bestAnswersCount = $event->user->answers()
            ->whereHas('question', function($query) {
                $query->whereColumn('questions.best_answer', 'answers.id');
            })
            ->count();

        upgrade_mission_for_user($event->user->id, $this->missionId, $bestAnswersCount, 1);
    }
}
