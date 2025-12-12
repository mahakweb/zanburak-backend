<?php

namespace App\Listeners\Mission\Purchases;

use App\Models\MissionCategory;
use App\Models\Score;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\Mission;
use App\Models\UserMission;

class FirstPurchaseListener
{
    protected $missionId;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->missionId = 'first-purchase';
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $userCourseCount = $event->user->courses()->count();
        if ($userCourseCount == 1) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
    }
}
