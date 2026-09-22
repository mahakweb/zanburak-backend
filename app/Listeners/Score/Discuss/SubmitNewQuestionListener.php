<?php

namespace App\Listeners\Score\Discuss;

class SubmitNewQuestionListener
{
    public function handle($event)
    {
        $question = $event->question;
        $user = $question->user;

        award_mission_exp(
            $user,
            'submit-question',
            'ثبت پرسش جدید: '.$question->subject
        );
        upgrade_mission_for_user($user->id, 'submit-question', 1, 1, false);

        $followers = $user->followers()->get();
        foreach ($followers as $follower) {
            $follower->notify(new \App\Notifications\Discuss\NewDiscussionNotification($question, $user));
        }
    }
}
