<?php

namespace App\Listeners\Report;

use App\Events\Report\ReportRejected;

class SendReportRejectedNotification
{
    public function handle(ReportRejected $event)
    {
        $event->user->notify(
            new \App\Notifications\Report\ReportRejectedNotification(
                $event->reportTitle,
                $event->reason
            )
        );
    }
}
