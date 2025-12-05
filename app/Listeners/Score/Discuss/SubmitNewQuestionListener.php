<?php

namespace App\Listeners\Score\Discuss;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SubmitNewQuestionListener
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
        $question = $event->question;
        $user = $question->user;

        $user->scores()->create([
            'description' => 'ثبت پرسش جدید: '.$question->subject,
            'score'       => 130,
        ]);
        
        // ارسال اطلاع‌رسانی به دنبال‌کنندگان کاربر
        $followers = $user->followers()->get();
        foreach ($followers as $follower) {
            sendNotification($follower, 'new-discussion', [
                'message' => "{$user->first_name} {$user->last_name} گفتگوی جدیدی با عنوان «{$question->subject}» ارسال کرد.",
                'subject' => 'گفتگوی جدید',
                'action_url' => frontendUrl("discuss/{$question->slug}"),
                'action_text' => 'مشاهده گفتگو',
                'sms_message' => "گفتگوی جدیدی از فردی که دنبال می‌کنید ارسال شد.",
            ]);
        }
    }
}
