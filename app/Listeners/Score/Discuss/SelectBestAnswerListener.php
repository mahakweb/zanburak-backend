<?php

namespace App\Listeners\Score\Discuss;

use App\Notifications\Discuss\SelectBestAnswerNotification;
use App\Services\ScoresService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SelectBestAnswerListener
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
        $answer = $event->answer;
        $question = $answer->question;
        $user = $answer->user;

        if($user->id != $question->user->id){
            $this->scoresService->awardScores(
                $user,
                'انتخاب پاسخ شما به عنوان بهترین پاسخ برای پرسش: <span class="font-bold">'.$question->subject.'</span>',
                1000
            );
        }

        // ارسال اطلاع‌رسانی - Notification فیزیکی خودش کانال‌ها را از NotificationService می‌گیرد
        $user->notify(new SelectBestAnswerNotification($answer));
    }
}
