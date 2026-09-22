<?php

namespace App\Listeners\Score\Discuss;

use App\Notifications\Discuss\SubmitAnswerNotification;

class SubmitNewAnswerListener
{
    public function handle($event)
    {
        $user = $event->user;
        $answer = $event->answer;
        $question = $answer->question;
        $firestAnswer = $question->answers->first();

        $action = 'submit-answer';
        $description = 'ثبت پاسخ برای پرسش ';

        if ($firestAnswer->id == $answer->id) {
            $action = 'submit-first-answer';
            $description = 'ثبت اولین پاسخ برای پرسش ';
        }

        $rewardMissionId = $action === 'submit-first-answer' ? 'submit-first-answer' : 'submit-answer';

        award_mission_exp(
            $user,
            $rewardMissionId,
            $description.$question->subject
        );
        upgrade_mission_for_user($user->id, $rewardMissionId, 1, 1, false);

        $question->user->notify(new SubmitAnswerNotification($question));

        if ($question->user_id != $user->id) {
            $isOldQuestion = $question->created_at->diffInDays(now()) > 30;

            if ($isOldQuestion) {
                $question->user->notify(new \App\Notifications\Discuss\OldQuestionAnsweredNotification($question, $user));
            } else {
                $question->user->notify(new \App\Notifications\Discuss\ReplyToDiscussionNotification($question, $user));
            }
        }

        $bookmarkers = $question->bookmarkers()->where('user_id', '!=', $user->id)->get();
        foreach ($bookmarkers as $bookmarker) {
            $bookmarker->notify(new \App\Notifications\Discuss\ReplyToFollowedDiscussionNotification($question));
        }

        $answererFollowers = $user->followers()->where('user_id', '!=', $user->id)->get();
        foreach ($answererFollowers as $follower) {
            $follower->notify(new \App\Notifications\Discuss\FollowedUserReplyToDiscussionNotification($question, $user));
        }
    }
}
