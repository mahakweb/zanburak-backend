<?php

namespace App\Listeners\Score\Payment;

use App\Events\Payment\VipUpgraded;

class VipUpgradedListener
{
    public function handle(VipUpgraded $event)
    {
        upgrade_mission_for_user($event->user->id, 'vip-upgraded');
    }
}
