<?php

namespace App\Listeners\Report;

use App\Events\Report\ReportApproved;

class SendReportApprovedNotification
{
    public function handle(ReportApproved $event)
    {
        $event->user->notify(
            new \App\Notifications\Report\ReportApprovedNotification(
                $event->reportTitle,
                $event->actionTaken
            )
        );
    }
}
