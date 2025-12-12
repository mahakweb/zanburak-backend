<?php

namespace App\Listeners\Score\Discuss;

use App\Services\ScoresService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SubmitNewQuestionListener
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
        $question = $event->question;
        $user = $question->user;

        $this->scoresService->awardScores(
            $user,
            'ثبت پرسش جدید: '.$question->subject,
            130
        );
        
        // ارسال اطلاع‌رسانی به دنبال‌کنندگان کاربر - Notification فیزیکی خودش کانال‌ها را از NotificationService می‌گیرد
        $followers = $user->followers()->get();
        foreach ($followers as $follower) {
            $follower->notify(new \App\Notifications\Discuss\NewDiscussionNotification($question, $user));
        }
    }
}
