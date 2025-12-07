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
        
        // ارسال اطلاع‌رسانی به دنبال‌کنندگان کاربر - Notification فیزیکی خودش کانال‌ها را از NotificationService می‌گیرد
        $followers = $user->followers()->get();
        foreach ($followers as $follower) {
            $follower->notify(new \App\Notifications\Discuss\NewDiscussionNotification($question, $user));
        }
    }
}
