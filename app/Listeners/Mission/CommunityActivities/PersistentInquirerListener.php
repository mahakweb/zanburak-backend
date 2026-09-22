<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;
use Carbon\Carbon;

class PersistentInquirerListener
{
    protected $missionId = 'persistent-inquirer';

    /**
     * Handle the event.
     * ماموریت پرسشگر کنجکاو: کاربری که حداقل 1 پرسش چالشی و بحث‌برانگیز را مطرح کرده است.
     * (پرسشی چالش برانگیز است که حداقل 10 پاسخ در روز اول پرسش داشته باشد) (سطح دارد)
     *
     * @param  CommunityActivityEvent  $event
     * @return void
     */
    public function handle(CommunityActivityEvent $event)
    {
        $question = null;
        $owner = null;

        if ($event->type === 'question' && $event->question) {
            $question = $event->question;
            $owner = $event->user;
        } elseif ($event->type === 'answer' && $event->answer) {
            $question = $event->answer->question;
            $owner = $question?->user;
        }

        if (!$question || !$owner || !$question->created_at) {
            return;
        }

        $firstDayEnd = $question->created_at->copy()->addDay();
        $answersInFirstDay = $question->answers()
            ->where('created_at', '>=', $question->created_at)
            ->where('created_at', '<=', $firstDayEnd)
            ->count();

        if ($answersInFirstDay < 10) {
            return;
        }

        $challengingQuestionsCount = $owner->questions()
            ->get()
            ->filter(function ($q) {
                if (!$q->created_at) {
                    return false;
                }
                $firstDayEnd = $q->created_at->copy()->addDay();

                return $q->answers()
                    ->where('created_at', '>=', $q->created_at)
                    ->where('created_at', '<=', $firstDayEnd)
                    ->count() >= 10;
            })
            ->count();

        sync_mission_progress_for_user($owner->id, $this->missionId, (int) $challengingQuestionsCount);
    }
}
