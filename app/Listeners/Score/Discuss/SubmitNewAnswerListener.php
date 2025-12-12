<?php

namespace App\Listeners\Score\Discuss;

use App\Notifications\Discuss\SubmitAnswerNotification;
use App\Services\ScoresService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SubmitNewAnswerListener
{
    protected $scoresService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $user = $event->user;
        $answer = $event->answer;
        $question = $answer->question;
        $firestAnswer = $question->answers->first();
        
        $action = 'submit-answer';
        $description = 'ثبت پاسخ برای پرسش ';

        if($firestAnswer->id == $answer->id){
            $action = 'submit-first-answer';
            $description = 'ثبت اولین پاسخ برای پرسش ';
        }

        $this->scoresService->awardScores(
            $user,
            $description.$question->subject,
            $action === 'submit-first-answer' ? 100 : 50
        );



        $question->user->notify(new SubmitAnswerNotification($question)); // notify to creator of question
        
        // ارسال اطلاع‌رسانی - Notification های فیزیکی خودشان کانال‌ها را از NotificationService می‌گیرند
        // به صاحب سوال (اگر خودش نباشد)
        if ($question->user_id != $user->id) {
            // بررسی اینکه آیا سوال قدیمی است (بیش از 30 روز)
            $isOldQuestion = $question->created_at->diffInDays(now()) > 30;
            
            if ($isOldQuestion) {
                $question->user->notify(new \App\Notifications\Discuss\OldQuestionAnsweredNotification($question, $user));
            } else {
                $question->user->notify(new \App\Notifications\Discuss\ReplyToDiscussionNotification($question, $user));
            }
        }
        
        // به bookmark کنندگان سوال (اگر خودشان نباشند)
        $bookmarkers = $question->bookmarkers()->where('user_id', '!=', $user->id)->get();
        foreach ($bookmarkers as $bookmarker) {
            $bookmarker->notify(new \App\Notifications\Discuss\ReplyToFollowedDiscussionNotification($question));
        }
        
        // به دنبال‌کنندگان کاربر پاسخ‌دهنده (اگر خودشان نباشند)
        $answererFollowers = $user->followers()->where('user_id', '!=', $user->id)->get();
        foreach ($answererFollowers as $follower) {
            $follower->notify(new \App\Notifications\Discuss\FollowedUserReplyToDiscussionNotification($question, $user));
        }
    }
}
