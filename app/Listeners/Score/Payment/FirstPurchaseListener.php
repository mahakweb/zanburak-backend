<?php

namespace App\Listeners\Score\Payment;

use App\Events\Payment\CoursePurchased;
use App\Services\ScoresService;

class FirstPurchaseListener
{
    protected $scoresService;

    public function __construct(ScoresService $scoresService)
    {
        $this->scoresService = $scoresService;
    }

    public function handle(CoursePurchased $event)
    {
        $user = $event->user;
        
        // بررسی اینکه آیا این اولین خرید است
        $purchaseCount = $user->payments()
            ->where('status', true)
            ->where('id', '<=', $event->payment->id)
            ->count();

        if ($purchaseCount === 1) {
            $this->scoresService->awardScores(
                $user,
                'اولین خرید دوره',
                200
            );
        }

        // امتیاز برای هر خرید
        $this->scoresService->awardScores(
            $user,
            "خرید دوره: {$event->course->title}",
            50
        );
    }
}

