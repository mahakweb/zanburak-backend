<?php

namespace App\Listeners\Score\Discuss;

use App\Notifications\Discuss\SelectBestAnswerNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SelectBestAnswerListener
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
        $answer = $event->answer;
        $question = $answer->question;
        $user = $answer->user;

        if($user->id != $question->user->id){
            $user->scores()->create([
                'description' => 'انتخاب پاسخ شما به عنوان بهترین پاسخ برای پرسش: <span class="font-bold">'.$question->subject.'</span>',
                'score'       => 1000,
            ]);
        }

        $user->notify(new SelectBestAnswerNotification($answer));

        // ارسال اطلاع‌رسانی از طریق سیستم جدید
        sendNotification($user, 'best-answer', [
            'message' => "پاسخ شما به سوال «{$question->subject}» به عنوان بهترین پاسخ انتخاب شد.",
            'subject' => 'بهترین پاسخ',
            'action_url' => frontendUrl("discuss/{$question->slug}"),
            'action_text' => 'مشاهده سوال',
            'sms_message' => "پاسخ شما به عنوان بهترین پاسخ انتخاب شد.",
        ]);
    }
}
