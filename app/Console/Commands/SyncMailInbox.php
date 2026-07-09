<?php

namespace App\Console\Commands;

use App\Services\Mail\ImapSyncService;
use Illuminate\Console\Command;

class SyncMailInbox extends Command
{
    protected $signature = 'mail:inbox-sync {--account= : Sync a single mailbox key (admin, support, info)}';

    protected $description = 'Sync inbound site mailboxes from IMAP into the admin inbox';

    public function handle(ImapSyncService $syncService): int
    {
        $accountKey = $this->option('account');

        if ($accountKey) {
            $account = \App\Models\MailAccount::where('key', $accountKey)->first();
            if (! $account) {
                $this->error("Mailbox [{$accountKey}] not found.");

                return self::FAILURE;
            }

            $result = $syncService->syncAccount($account);
            $this->info("Synced {$result['synced']} message(s) for [{$accountKey}].");

            if (! empty($result['message'])) {
                $this->warn($result['message']);
            }

            return self::SUCCESS;
        }

        $results = $syncService->syncAll();

        foreach ($results as $key => $result) {
            if (! empty($result['skipped'])) {
                $this->warn("[{$key}] skipped: ".($result['message'] ?? 'unknown reason'));
                continue;
            }

            $this->info("[{$key}] synced {$result['synced']} message(s).");
        }

        return self::SUCCESS;
    }
}
