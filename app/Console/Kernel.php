<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('messenger:prune')->hourly();
        // Drain Redis write-behind outbox. Sub-minute cadence when supported;
        // everyMinute remains a safe fallback for older schedulers.
        $flush = $schedule->command('messenger:flush-outbox')->withoutOverlapping(1);
        if (method_exists($flush, 'everyTwoSeconds')) {
            $flush->everyTwoSeconds();
        } else {
            $flush->everyMinute();
        }
        $schedule->command('quiz:expire-attempts')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
