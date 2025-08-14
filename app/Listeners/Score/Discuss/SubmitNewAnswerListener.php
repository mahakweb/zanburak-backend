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
    }
}
