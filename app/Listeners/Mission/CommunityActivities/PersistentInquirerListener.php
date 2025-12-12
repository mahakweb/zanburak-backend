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
        if ($event->type !== 'question' || !$event->question) {
            return;
        }

        $question = $event->question;
        $firstDayEnd = $question->created_at->copy()->addDay();

        // تعداد پاسخ‌ها در 24 ساعت اول
        $answersInFirstDay = $question->answers()
            ->where('created_at', '>=', $question->created_at)
            ->where('created_at', '<=', $firstDayEnd)
            ->count();

        if ($answersInFirstDay >= 10) {
            // تعداد کل سوالات چالشی کاربر
            $challengingQuestionsCount = $event->user->questions()
                ->get()
                ->filter(function($q) {
                    $firstDayEnd = $q->created_at->copy()->addDay();
                    return $q->answers()
                        ->where('created_at', '>=', $q->created_at)
                        ->where('created_at', '<=', $firstDayEnd)
                        ->count() >= 10;
                })
                ->count();

            upgrade_mission_for_user($event->user->id, $this->missionId, $challengingQuestionsCount, 1);
        }
    }
}
