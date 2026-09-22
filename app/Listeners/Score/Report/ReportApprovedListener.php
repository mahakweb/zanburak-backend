<?php

namespace App\Listeners\Score\Report;

use App\Events\Score\Report\ReportApprovedForScores;

class ReportApprovedListener
{
    public function handle(ReportApprovedForScores $event)
    {
        award_mission_exp(
            $event->user,
            'report-approved',
            'ارسال گزارش مفید: ' . ($event->report->report ?? $event->report->id)
        );
        upgrade_mission_for_user($event->user->id, 'report-approved', 1, 1, false);
    }
}
