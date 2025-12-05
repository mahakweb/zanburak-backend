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
        
        // ارسال اطلاع‌رسانی از طریق سیستم جدید
        // به صاحب سوال (اگر خودش نباشد)
        if ($question->user_id != $user->id) {
            $answererName = $user->first_name . ' ' . $user->last_name;
            sendNotification($question->user, 'reply-to-discussion', [
                'message' => "{$answererName} به گفتگوی شما «{$question->subject}» پاسخ داد.",
                'subject' => 'پاسخ به گفتگو',
                'action_url' => frontendUrl("discuss/{$question->slug}"),
                'action_text' => 'مشاهده پاسخ',
                'sms_message' => "شما یک پاسخ جدید به گفتگوی خود دریافت کرده‌اید.",
            ]);
        }
        
        // به bookmark کنندگان سوال (اگر خودشان نباشند)
        $bookmarkers = $question->bookmarkers()->where('user_id', '!=', $user->id)->get();
        foreach ($bookmarkers as $bookmarker) {
            sendNotification($bookmarker, 'reply-to-followed-discussion', [
                'message' => "به گفتگویی که ذخیره کرده‌اید «{$question->subject}» پاسخ جدیدی داده شد.",
                'subject' => 'پاسخ به گفتگوی ذخیره شده',
                'action_url' => frontendUrl("discuss/{$question->slug}"),
                'action_text' => 'مشاهده پاسخ',
                'sms_message' => "به گفتگویی که ذخیره کرده‌اید پاسخ جدیدی داده شد.",
            ]);
        }
        
        // به دنبال‌کنندگان کاربر پاسخ‌دهنده (اگر خودشان نباشند)
        $answererFollowers = $user->followers()->where('user_id', '!=', $user->id)->get();
        foreach ($answererFollowers as $follower) {
            sendNotification($follower, 'followed-user-reply-to-discussion', [
                'message' => "{$user->first_name} {$user->last_name} که دنبال می‌کنید به گفتگویی پاسخ داد.",
                'subject' => 'پاسخ به گفتگو',
                'action_url' => frontendUrl("discuss/{$question->slug}"),
                'action_text' => 'مشاهده پاسخ',
                'sms_message' => "فردی که دنبال می‌کنید به گفتگویی پاسخ داد.",
            ]);
        }
    }
}
