<?php

namespace App\Console\Commands;

use App\Services\Messenger\MessengerOutbox;
use App\Services\Messenger\RealtimeBus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Probe hot-path stage timings (Redis / Postgres / outbox) for messenger latency budgets.
 *
 * Target: end-to-end peer delivery < 300ms with a warm WebSocket (whisper path
 * bypasses PHP; this command measures the durable Redis ack path stages).
 */
class MessengerLatencyProbe extends Command
{
    protected $signature = 'messenger:latency-probe {--rounds=20 : Samples per stage}';

    protected $description = 'Measure messenger hot-path stage latencies (Redis vs Postgres)';

    public function handle(RealtimeBus $bus, MessengerOutbox $outbox): int
    {
        $rounds = max(5, (int) $this->option('rounds'));

        $this->info('Messenger latency probe');
        $this->line('Redis enabled: '.($bus->isEnabled() ? 'yes' : 'no'));
        $this->line('Redis available: '.($bus->isAvailable() ? 'yes' : 'no'));
        $this->line('Hot path active: '.($outbox->isActive() ? 'yes' : 'no'));
        $this->newLine();

        $stages = [];

        try {
            $stages['redis_ping'] = $this->measure($rounds, function () {
                Redis::connection(config('messenger.redis.connection', 'default'))->ping();
            });
        } catch (\Throwable $e) {
            $this->error('Redis probe failed: '.$e->getMessage());
            $this->warn('Fix Redis persistence (or set stop-writes-on-bgsave-error no) — hot path requires writable Redis.');
        }

        if ($outbox->isActive()) {
            try {
                $stages['redis_incr_seq'] = $this->measure($rounds, function () {
                    Redis::connection()->incr(config('messenger.redis.prefix', 'messenger').':probe:seq');
                });

                $stages['redis_lpush_outbox'] = $this->measure($rounds, function () {
                    $key = config('messenger.redis.prefix', 'messenger').':probe:outbox';
                    Redis::connection()->lpush($key, '{"op":"probe"}');
                    Redis::connection()->ltrim($key, 0, 0);
                });
            } catch (\Throwable $e) {
                $this->warn('Redis write probe failed: '.$e->getMessage());
            }
        }

        try {
            $stages['pgsql_select_1'] = $this->measure($rounds, function () {
                DB::select('select 1');
            });

            $stages['pgsql_messages_max'] = $this->measure($rounds, function () {
                DB::table('messages')->max('id');
            });
        } catch (\Throwable $e) {
            $this->warn('Postgres probe failed: '.$e->getMessage());
        }

        if ($stages === []) {
            return self::FAILURE;
        }

        $this->table(
            ['Stage', 'p50 (ms)', 'p95 (ms)', 'avg (ms)', 'Budget note'],
            collect($stages)->map(function ($stats, $name) {
                return [
                    $name,
                    number_format($stats['p50'], 2),
                    number_format($stats['p95'], 2),
                    number_format($stats['avg'], 2),
                    $this->budgetNote($name, $stats['p95']),
                ];
            })->values()->all()
        );

        $this->newLine();
        $this->line('Architecture budgets (warm path):');
        $this->line('  1) Optimistic UI paint:           ~0ms (client)');
        $this->line('  2) E2E encrypt (warm keys):        < 15ms (client WebCrypto)');
        $this->line('  3) WS whisper → peer:              < 50ms (Reverb, no PHP)');
        $this->line('  4) Redis hot ack + Reverb fan-out: < 80ms (header X-Messenger-Send-Ms)');
        $this->line('  5) Postgres write-behind:          async (not on live path)');
        $this->line('Target: steps 1–3 for recipient paint < 300ms RTT.');

        if (! $outbox->isActive()) {
            $this->warn('Hot path inactive — enable MESSENGER_REDIS=true and ensure Redis is up.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{p50: float, p95: float, avg: float}
     */
    protected function measure(int $rounds, callable $fn): array
    {
        $samples = [];
        for ($i = 0; $i < $rounds; $i++) {
            $t0 = hrtime(true);
            $fn();
            $samples[] = (hrtime(true) - $t0) / 1e6;
        }
        sort($samples);
        $n = count($samples);

        return [
            'p50' => $samples[(int) floor(($n - 1) * 0.5)],
            'p95' => $samples[(int) floor(($n - 1) * 0.95)],
            'avg' => array_sum($samples) / $n,
        ];
    }

    protected function budgetNote(string $name, float $p95): string
    {
        if (str_starts_with($name, 'redis')) {
            return $p95 < 5 ? 'OK' : 'SLOW — check Redis locality';
        }
        if (str_starts_with($name, 'pgsql')) {
            return $p95 < 20 ? 'OK (must stay off live path)' : 'SLOW DB — hot path required';
        }

        return '';
    }
}
