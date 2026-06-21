<?php

namespace App\Console\Commands;

use App\Models\Quiz\QuizAttempt;
use Illuminate\Console\Command;

class ExpireQuizAttempts extends Command
{
    protected $signature = 'quiz:expire-attempts';

    protected $description = 'Mark overdue in-progress quiz attempts as expired';

    public function handle(): int
    {
        $expired = QuizAttempt::query()
            ->where('status', 'in_progress')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired']);

        $this->info("Expired {$expired} quiz attempt(s).");

        return self::SUCCESS;
    }
}
