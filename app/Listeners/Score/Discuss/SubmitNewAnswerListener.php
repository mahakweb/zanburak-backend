<?php

namespace App\Listeners\Score\Discuss;

use App\Notifications\Discuss\SubmitAnswerNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SubmitNewAnswerListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        $description = 'ثبت پاسخ برای پرسش ';
        $score = 50;




        if($firestAnswer->id == $answer->id){
            $description = 'ثبت اولین پاسخ برای پرسش ';
            $score = 100;
        }

        // if($user->id != $question->user->id && $question->answers->where('user_id', $user->id)->count() == 1) //count == 1 mean there is only this answer for this question
        // {
            $user->scores()->create([
                'description' => $description.$question->subject,
                'score'       => $score,
            ]);
        // }



        $question->user->notify(new SubmitAnswerNotification($question)); // notify to creator of question
        
        // ارسال اطلاع‌رسانی - Notification های فیزیکی خودشان کانال‌ها را از NotificationService می‌گیرند
        // به صاحب سوال (اگر خودش نباشد)
        if ($question->user_id != $user->id) {
            $question->user->notify(new \App\Notifications\Discuss\ReplyToDiscussionNotification($question, $user));
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
