<?php

namespace App\Events\Report;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportApproved
{
    use Dispatchable, SerializesModels;

    public $user;
    public $reportTitle;
    public $actionTaken;

    public function __construct(User $user, string $reportTitle, string $actionTaken = null)
    {
        $this->user = $user;
        $this->reportTitle = $reportTitle;
        $this->actionTaken = $actionTaken;
    }
}
