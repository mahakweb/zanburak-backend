<?php

namespace App\Listeners\Mission\CommunityActivities;

use App\Events\Mission\CommunityActivityEvent;

class IssueReporterListener
{
    protected $missionId = 'issue-reporter';

    /**
     * Handle the event.
     * ماموریت گزارشگر دقیق: کاربری که حداقل 1 گزارش معتبر و تأیید شده توسط مدیریت سایت را ارائه داده است. (سطح دارد)
     *
     * @param  CommunityActivityEvent  $event
     * @return void
     */
    public function handle(CommunityActivityEvent $event)
    {
        if ($event->type !== 'report' || !$event->report) {
            return;
        }

        if (!$event->report->status) {
            return;
        }

        $approvedReportsCount = $event->user->reports()
            ->where('status', true)
            ->count();

        if ($approvedReportsCount < 1) {
            return;
        }

        sync_mission_progress_for_user($event->user->id, $this->missionId, (int) $approvedReportsCount);
    }
}
