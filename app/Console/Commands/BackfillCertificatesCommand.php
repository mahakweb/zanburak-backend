<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Services\Certificate\CertificateIssuanceService;
use Illuminate\Console\Command;

class BackfillCertificatesCommand extends Command
{
    protected $signature = 'certificates:backfill {--limit=500 : Max certificates to process}';

    protected $description = 'Backfill serial numbers and verification tokens for existing certificates';

    public function handle(CertificateIssuanceService $issuance): int
    {
        $limit = (int) $this->option('limit');
        $query = Certificate::query()
            ->where(function ($q) {
                $q->whereNull('serial_number')
                    ->orWhereNull('verification_token');
            })
            ->orderBy('id');

        $total = (clone $query)->count();
        $this->info("Found {$total} certificate(s) needing backfill.");

        $processed = 0;
        $query->limit($limit)->chunkById(50, function ($certificates) use ($issuance, &$processed) {
            foreach ($certificates as $certificate) {
                $issuance->backfill($certificate);
                $processed++;
                $this->line("Backfilled #{$certificate->id} ({$certificate->uuid})");
            }
        });

        $this->info("Processed {$processed} certificate(s).");

        return self::SUCCESS;
    }
}
