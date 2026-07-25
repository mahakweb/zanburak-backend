<?php

namespace App\Console\Commands;

use App\Services\Messenger\MessengerOutbox;
use Illuminate\Console\Command;

class FlushMessengerOutbox extends Command
{
    protected $signature = 'messenger:flush-outbox {--limit=200 : Max outbox items to flush}';

    protected $description = 'Flush Redis messenger outbox (messages, events, receipts) into the database';

    public function handle(MessengerOutbox $outbox): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $flushed = 0;

        // Drain in chunks until empty or a safety cap.
        for ($i = 0; $i < 20; $i++) {
            $n = $outbox->flush($limit);
            $flushed += $n;
            if ($n < $limit) {
                break;
            }
        }

        if ($flushed > 0) {
            $this->info("Flushed {$flushed} messenger outbox item(s).");
        }

        return self::SUCCESS;
    }
}
