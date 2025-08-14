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
        // $firstPurchaseMission = Mission::where('id', $this->missionId)->first();
        // if (!$firstPurchaseMission) {
        //     $firstPurchaseMission = Mission::create([
        //         "id" => $this->missionId,
        //         "category_id" => 1,
        //         "title" => "اولین خرید",
        //         "description" => "اولین خرید دوره",
        //         "levels" => json_encode([
        //             1 => [
        //                 "goal" => 1,
        //                 "exp" => 50,
        //                 "requirements" => []
        //             ],
        //             2 => [
        //                 "goal" => 3,
        //                 "exp" => 100,
        //                 "requirements" => []
        //             ],
        //             3 => [
        //                 "goal" => 5,
        //                 "exp" => 500,
        //                 "requirements" => [
        //                     [
        //                         "id" => "commited-buyer",
        //                         "title" => "خریدار متعهد",
        //                         "level" => 1
        //                     ],
        //                     [
        //                         "id" => "commited-buyer",
        //                         "title" => "خریدار متعهد",
        //                         "level" => 2
        //                     ]
        //                 ]
        //             ]
        //         ]),
        //     ]);
        // }
        $userCourseCount = $event->user->courses()->count();
        if ($userCourseCount == 1) {
            upgrade_mission_for_user($event->user->id, $this->missionId);
        }
        return;
    }
}
