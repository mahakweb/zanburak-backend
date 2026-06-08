<?php

namespace App\Console\Commands;

use App\Services\Messenger\MessengerService;
use Illuminate\Console\Command;

class PruneMessengerEvents extends Command
{
    protected $signature = 'messenger:prune';

    protected $description = 'Prune old messenger realtime events from the database';

    public function handle(MessengerService $messenger): int
    {
        $deleted = $messenger->pruneOldEvents();
        $this->info("Pruned {$deleted} messenger events.");

        return self::SUCCESS;
    }
}
