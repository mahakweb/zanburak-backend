<?php

namespace App\Events\Report;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportRejected
{
    use Dispatchable, SerializesModels;

    public $user;
    public $reportTitle;
    public $reason;

    public function __construct(User $user, string $reportTitle, string $reason = null)
    {
        $this->user = $user;
        $this->reportTitle = $reportTitle;
        $this->reason = $reason;
    }
}
