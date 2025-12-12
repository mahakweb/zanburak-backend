<?php

namespace App\Events\Score\Report;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportApprovedForScores
{
    use Dispatchable, SerializesModels;

    public $user;
    public $report;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $user, Report $report)
    {
        $this->user = $user;
        $this->report = $report;
    }
}

