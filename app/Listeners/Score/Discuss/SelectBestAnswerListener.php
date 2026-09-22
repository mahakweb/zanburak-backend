<?php

namespace App\Listeners\Score\Discuss;

use App\Events\Mission\CommunityActivityEvent;
use App\Notifications\Discuss\SelectBestAnswerNotification;

class SelectBestAnswerListener
{
    public function handle($event)
    {
        $answer = $event->answer;
        $question = $answer->question()->first();
        $user = $answer->user;

        if ($question && $user && $user->id != $question->user_id) {
            award_mission_exp(
                $user,
                'best-answer',
                'انتخاب پاسخ شما به عنوان بهترین پاسخ برای پرسش: <span class="font-bold">'.$question->subject.'</span>'
            );
            upgrade_mission_for_user($user->id, 'best-answer', 1, 1, false);
        }

        if ($user) {
            try {
                $user->notify(new SelectBestAnswerNotification($answer));
            } catch (\Throwable $e) {
                \Log::warning('Best answer notification failed: '.$e->getMessage());
            }
        }

        if ($user) {
            event(new CommunityActivityEvent($user, 'answer', $answer));
        }
    }
}
