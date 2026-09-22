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

        $bestAnswersCount = $event->user->answers()
            ->whereIn('id', function ($query) {
                $query->select('best_answer')
                    ->from('questions')
                    ->whereNotNull('best_answer');
            })
            ->count();

        sync_mission_progress_for_user($event->user->id, $this->missionId, (int) $bestAnswersCount);
    }
}
