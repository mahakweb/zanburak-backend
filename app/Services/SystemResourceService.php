<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SystemResourceService
{
    private const HISTORY_KEY = 'admin:system_resources:history';

    private const HISTORY_LIMIT = 180;

    private const DIR_SIZE_CACHE_TTL = 300;

    private const METRICS_CACHE_TTL = 120;

    public function snapshot(): array
    {
        $started = microtime(true);

        $database = $this->getDatabaseInfo();
        $cache = $this->getCacheInfo();
        $benchmarks = $this->runBenchmarks();

        $data = [
            'collected_at' => now()->toIso8601String(),
            'server' => $this->getServerInfo(),
            'cpu' => $this->getCpuInfo(),
            'memory' => $this->getMemoryInfo(),
            'swap' => $this->getSwapInfo(),
            'disk' => $this->getDiskInfo(),
            'storage' => $this->getStorageBreakdown(),
            'php' => $this->getPhpInfo(),
            'application' => $this->getApplicationInfo(),
            'database' => $database,
            'cache' => $cache,
            'redis' => $this->getRedisInfo(),
            'queue' => $this->getQueueInfo(),
            'logs' => $this->getLogsInfo(),
            'security' => $this->getSecurityAudit(),
            'benchmarks' => $benchmarks,
            'metrics' => $this->getApplicationMetrics(),
            'services' => $this->getServicesHealth($database, $cache),
            'network' => $this->getNetworkInfo(),
        ];

        $data['health'] = $this->buildHealthScore($data);
        $data['alerts'] = $this->buildAlerts($data);
        $data['performance'] = [
            'collection_time_ms' => round((microtime(true) - $started) * 1000, 2),
        ];

        $this->recordHistory($data);

        return $data;
    }

    public function getHistory(): array
    {
        return Cache::get(self::HISTORY_KEY, []);
    }

    public function getHistoryCharts(): array
    {
        $history = $this->getHistory();

        $labels = [];
        $cpu = [];
        $memory = [];
        $disk = [];
        $phpMemory = [];
        $databaseSize = [];
        $healthScore = [];
        $cacheLatency = [];
        $dbLatency = [];

        foreach ($history as $point) {
            $labels[] = $point['timestamp'] ?? '';
            $cpu[] = $point['cpu_usage_percent'] ?? null;
            $memory[] = $point['memory_usage_percent'] ?? null;
            $disk[] = $point['disk_usage_percent'] ?? null;
            $phpMemory[] = $point['php_memory_mb'] ?? null;
            $databaseSize[] = $point['database_size_mb'] ?? null;
            $healthScore[] = $point['health_score'] ?? null;
            $cacheLatency[] = $point['cache_latency_ms'] ?? null;
            $dbLatency[] = $point['db_latency_ms'] ?? null;
        }

        return [
            'labels' => $labels,
            'cpu_usage_percent' => $cpu,
            'memory_usage_percent' => $memory,
            'disk_usage_percent' => $disk,
            'php_memory_mb' => $phpMemory,
            'database_size_mb' => $databaseSize,
            'health_score' => $healthScore,
            'cache_latency_ms' => $cacheLatency,
            'db_latency_ms' => $dbLatency,
        ];
    }

    private function recordHistory(array $snapshot): void
    {
        $history = Cache::get(self::HISTORY_KEY, []);

        $history[] = [
            'timestamp' => $snapshot['collected_at'],
            'cpu_usage_percent' => $snapshot['cpu']['usage_percent'] ?? null,
            'memory_usage_percent' => $snapshot['memory']['system']['usage_percent'] ?? null,
            'disk_usage_percent' => $snapshot['disk']['primary']['usage_percent'] ?? null,
            'php_memory_mb' => $snapshot['memory']['php']['current_mb'] ?? null,
            'database_size_mb' => $snapshot['database']['size_mb'] ?? null,
            'health_score' => $snapshot['health']['score'] ?? null,
            'cache_latency_ms' => $snapshot['cache']['latency_ms'] ?? null,
            'db_latency_ms' => $snapshot['benchmarks']['database_read_ms'] ?? null,
        ];

        if (count($history) > self::HISTORY_LIMIT) {
            $history = array_slice($history, -self::HISTORY_LIMIT);
        }

        Cache::put(self::HISTORY_KEY, $history, now()->addDays(3));
    }

    private function buildHealthScore(array $data): array
    {
        $factors = [];

        $cpu = $data['cpu']['usage_percent'] ?? null;
        if ($cpu !== null) {
            $factors[] = ['weight' => 20, 'score' => max(0, 100 - $cpu)];
        }

        $memory = $data['memory']['system']['usage_percent'] ?? null;
        if ($memory !== null) {
            $factors[] = ['weight' => 20, 'score' => max(0, 100 - $memory)];
        }

        $disk = $data['disk']['primary']['usage_percent'] ?? null;
        if ($disk !== null) {
            $factors[] = ['weight' => 15, 'score' => max(0, 100 - $disk)];
        }

        $servicesScore = $data['services']['summary']['score_percent'] ?? 0;
        $factors[] = ['weight' => 25, 'score' => $servicesScore];

        $securityScore = $data['security']['score'] ?? 50;
        $factors[] = ['weight' => 10, 'score' => $securityScore];

        $benchmarkDb = $data['benchmarks']['database_read_ms'] ?? 100;
        $factors[] = ['weight' => 5, 'score' => max(0, min(100, 100 - ($benchmarkDb * 2)))];

        $benchmarkCache = $data['benchmarks']['cache_read_ms'] ?? 50;
        $factors[] = ['weight' => 5, 'score' => max(0, min(100, 100 - ($benchmarkCache * 3)))];

        $totalWeight = array_sum(array_column($factors, 'weight'));
        $weighted = 0;
        foreach ($factors as $factor) {
            $weighted += $factor['score'] * $factor['weight'];
        }

        $score = $totalWeight > 0 ? round($weighted / $totalWeight, 1) : 0;

        return [
            'score' => $score,
            'grade' => $this->healthGrade($score),
            'status' => $this->healthStatus($score),
            'label' => $this->healthLabel($score),
        ];
    }

    private function buildAlerts(array $data): array
    {
        $alerts = [];

        $push = static function (string $level, string $title, string $message) use (&$alerts): void {
            $alerts[] = compact('level', 'title', 'message');
        };

        if (($data['cpu']['status'] ?? '') === 'critical') {
            $push('critical', 'بار پردازنده بحرانی', 'مصرف CPU از ۹۰٪ گذشته است.');
        } elseif (($data['cpu']['status'] ?? '') === 'warning') {
            $push('warning', 'بار پردازنده بالا', 'مصرف CPU از ۷۵٪ گذشته است.');
        }

        if (($data['memory']['system']['status'] ?? '') === 'critical') {
            $push('critical', 'حافظه بحرانی', 'حافظه سیستم تقریباً پر شده است.');
        } elseif (($data['memory']['system']['status'] ?? '') === 'warning') {
            $push('warning', 'حافظه بالا', 'مصرف RAM از ۷۵٪ گذشته است.');
        }

        if (($data['disk']['primary']['status'] ?? '') === 'critical') {
            $push('critical', 'فضای دیسک بحرانی', 'فضای دیسک کمتر از ۱۰٪ باقی مانده.');
        } elseif (($data['disk']['primary']['status'] ?? '') === 'warning') {
            $push('warning', 'فضای دیسک کم', 'مصرف دیسک از ۷۵٪ گذشته است.');
        }

        if (($data['queue']['failed_jobs'] ?? 0) > 0) {
            $push('warning', 'Job ناموفق', $data['queue']['failed_jobs'] . ' job در صف failed وجود دارد.');
        }

        if (($data['queue']['pending_jobs'] ?? 0) > 100) {
            $push('warning', 'صف شلوغ', $data['queue']['pending_jobs'] . ' job در انتظار پردازش است.');
        }

        if (($data['application']['debug'] ?? false) && ($data['application']['env'] ?? '') === 'production') {
            $push('critical', 'Debug در Production', 'حالت debug در محیط production فعال است.');
        }

        if (($data['security']['checks']['https'] ?? true) === false && ($data['application']['env'] ?? '') === 'production') {
            $push('warning', 'HTTPS غیرفعال', 'APP_URL از HTTPS استفاده نمی‌کند.');
        }

        if (($data['logs']['laravel']['error_lines_24h'] ?? 0) > 50) {
            $push('warning', 'خطاهای زیاد در لاگ', $data['logs']['laravel']['error_lines_24h'] . ' خطای ERROR در ۲۴ ساعت اخیر.');
        }

        if (($data['cache']['status'] ?? '') === 'error') {
            $push('critical', 'کش در دسترس نیست', 'اتصال به سرویس کش برقرار نشد.');
        }

        if (($data['database']['status'] ?? '') !== 'connected') {
            $push('critical', 'دیتابیس قطع', 'اتصال به پایگاه داده برقرار نیست.');
        }

        if (($data['benchmarks']['database_read_ms'] ?? 0) > 100) {
            $push('warning', 'کندی دیتابیس', 'زمان پاسخ DB: ' . $data['benchmarks']['database_read_ms'] . 'ms');
        }

        usort($alerts, fn ($a, $b) => ['critical' => 0, 'warning' => 1, 'info' => 2][$a['level']] <=> ['critical' => 0, 'warning' => 1, 'info' => 2][$b['level']]);

        return $alerts;
    }

    private function runBenchmarks(): array
    {
        $dbMs = null;
        try {
            $start = microtime(true);
            DB::selectOne('SELECT 1 as ping');
            $dbMs = round((microtime(true) - $start) * 1000, 2);
        } catch (\Throwable $e) {
            $dbMs = null;
        }

        $cacheReadMs = null;
        $cacheWriteMs = null;
        try {
            $key = 'bench:' . Str::random(8);
            $start = microtime(true);
            Cache::put($key, str_repeat('x', 1024), 30);
            $cacheWriteMs = round((microtime(true) - $start) * 1000, 2);

            $start = microtime(true);
            Cache::get($key);
            $cacheReadMs = round((microtime(true) - $start) * 1000, 2);
            Cache::forget($key);
        } catch (\Throwable $e) {
            // ignore
        }

        $diskWriteMs = null;
        try {
            $path = storage_path('framework/cache/system_bench_' . Str::random(6) . '.tmp');
            $start = microtime(true);
            file_put_contents($path, str_repeat('x', 4096));
            $diskWriteMs = round((microtime(true) - $start) * 1000, 2);
            @unlink($path);
        } catch (\Throwable $e) {
            // ignore
        }

        return [
            'database_read_ms' => $dbMs,
            'cache_read_ms' => $cacheReadMs,
            'cache_write_ms' => $cacheWriteMs,
            'disk_write_ms' => $diskWriteMs,
            'chart' => [
                'labels' => ['DB Read', 'Cache Read', 'Cache Write', 'Disk Write'],
                'values' => [
                    $dbMs ?? 0,
                    $cacheReadMs ?? 0,
                    $cacheWriteMs ?? 0,
                    $diskWriteMs ?? 0,
                ],
                'colors' => ['#06b6d4', '#3b82f6', '#8b5cf6', '#f59e0b'],
            ],
        ];
    }

    private function getServerInfo(): array
    {
        $hostname = gethostname() ?: php_uname('n');

        return [
            'hostname' => $hostname,
            'os' => php_uname('s') . ' ' . php_uname('r'),
            'architecture' => php_uname('m'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? PHP_SAPI,
            'sapi' => PHP_SAPI,
            'timezone' => config('app.timezone'),
            'uptime_seconds' => $this->getUptimeSeconds(),
            'uptime_human' => $this->formatDuration($this->getUptimeSeconds()),
            'processes' => $this->getProcessCount(),
            'cpu_model' => $this->getCpuModel(),
        ];
    }

    private function getCpuInfo(): array
    {
        $cores = $this->getCpuCores();
        $load = $this->getLoadAverage();
        $usagePercent = $this->getCpuUsagePercent($cores, $load);

        return [
            'cores' => $cores,
            'model' => $this->getCpuModel(),
            'load_average' => $load,
            'usage_percent' => $usagePercent,
            'status' => $this->usageStatus($usagePercent),
            'chart' => $load ? [
                'labels' => ['۱ دقیقه', '۵ دقیقه', '۱۵ دقیقه'],
                'values' => [$load['1min'], $load['5min'], $load['15min']],
            ] : null,
        ];
    }

    private function getCpuUsagePercent(int $cores, ?array $load): ?float
    {
        if ($load !== null && $cores > 0) {
            return min(100, round(($load['1min'] / $cores) * 100, 1));
        }

        $windows = $this->getWindowsCpuUsage();
        if ($windows !== null) {
            return $windows;
        }

        return null;
    }

    private function getMemoryInfo(): array
    {
        $system = $this->getSystemMemory();
        $phpCurrent = memory_get_usage(true);
        $phpPeak = memory_get_peak_usage(true);
        $phpLimit = $this->parseSize(ini_get('memory_limit'));

        $phpUsagePercent = $phpLimit > 0
            ? round(($phpCurrent / $phpLimit) * 100, 1)
            : null;

        return [
            'system' => $system,
            'php' => [
                'current_bytes' => $phpCurrent,
                'current_mb' => round($phpCurrent / 1048576, 2),
                'peak_bytes' => $phpPeak,
                'peak_mb' => round($phpPeak / 1048576, 2),
                'limit_bytes' => $phpLimit,
                'limit_human' => ini_get('memory_limit'),
                'usage_percent' => $phpUsagePercent,
                'status' => $this->usageStatus($phpUsagePercent),
            ],
            'chart' => [
                'labels' => ['استفاده‌شده', 'آزاد'],
                'values' => [
                    (int) ($system['used_bytes'] ?? 0),
                    (int) ($system['available_bytes'] ?? 0),
                ],
                'colors' => ['#8b5cf6', '#e5e7eb'],
            ],
        ];
    }

    private function getSwapInfo(): array
    {
        if (PHP_OS_FAMILY !== 'Linux' || !is_readable('/proc/meminfo')) {
            return ['available' => false];
        }

        $info = [];
        foreach (file('/proc/meminfo', FILE_IGNORE_NEW_LINES) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode(':', $line, 2));
            $info[$key] = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT) * 1024;
        }

        $total = $info['SwapTotal'] ?? 0;
        $free = $info['SwapFree'] ?? 0;
        $used = max(0, $total - $free);
        $usagePercent = $total > 0 ? round(($used / $total) * 100, 1) : 0;

        return [
            'available' => $total > 0,
            'total_bytes' => $total,
            'used_bytes' => $used,
            'free_bytes' => $free,
            'total_human' => $this->formatBytes($total),
            'used_human' => $this->formatBytes($used),
            'usage_percent' => $usagePercent,
            'status' => $this->usageStatus($usagePercent),
        ];
    }

    private function getDiskInfo(): array
    {
        $paths = [
            'application' => base_path(),
            'storage' => storage_path(),
            'public' => public_path(),
        ];

        $disks = [];
        foreach ($paths as $label => $path) {
            $disks[$label] = $this->getPathDiskUsage($path);
        }

        $primary = $disks['application'] ?? $this->getPathDiskUsage(base_path());

        return [
            'primary' => $primary,
            'paths' => $disks,
            'chart' => [
                'labels' => ['استفاده‌شده', 'آزاد'],
                'values' => [
                    (int) ($primary['used_bytes'] ?? 0),
                    (int) ($primary['free_bytes'] ?? 0),
                ],
                'colors' => ['#f59e0b', '#e5e7eb'],
            ],
        ];
    }

    private function getStorageBreakdown(): array
    {
        $directories = [
            'app/public' => storage_path('app/public'),
            'app/private' => storage_path('app'),
            'framework/cache' => storage_path('framework/cache'),
            'framework/sessions' => storage_path('framework/sessions'),
            'framework/views' => storage_path('framework/views'),
            'logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
            'public/uploads' => public_path('uploads'),
        ];

        $items = [];
        $total = 0;

        foreach ($directories as $label => $path) {
            if (!is_dir($path)) {
                continue;
            }
            $bytes = $this->getDirectorySize($path);
            $total += $bytes;
            $items[] = [
                'label' => $label,
                'path' => $path,
                'bytes' => $bytes,
                'human' => $this->formatBytes($bytes),
            ];
        }

        foreach ($items as &$item) {
            $item['percent'] = $total > 0 ? round(($item['bytes'] / $total) * 100, 1) : 0;
        }
        unset($item);

        usort($items, fn ($a, $b) => $b['bytes'] <=> $a['bytes']);

        $colors = ['#3b82f6', '#8b5cf6', '#f59e0b', '#10b981', '#ef4444', '#06b6d4', '#ec4899', '#6366f1'];

        return [
            'total_bytes' => $total,
            'total_human' => $this->formatBytes($total),
            'items' => $items,
            'largest_log_files' => $this->getLargestLogFiles(),
            'chart' => [
                'labels' => array_map(fn ($row) => $row['label'], $items),
                'values' => array_map(fn ($row) => $row['bytes'], $items),
                'colors' => array_slice($colors, 0, count($items)),
            ],
        ];
    }

    private function getPhpInfo(): array
    {
        $extensions = array_values(array_filter(
            get_loaded_extensions(),
            fn ($ext) => in_array(strtolower($ext), [
                'pdo', 'pdo_mysql', 'mysqli', 'mbstring', 'openssl', 'curl', 'gd', 'imagick',
                'redis', 'memcached', 'zip', 'intl', 'bcmath', 'fileinfo', 'json', 'tokenizer',
                'xml', 'ctype', 'sodium', 'opcache', 'pcntl', 'posix', 'sockets',
            ], true)
        ));
        sort($extensions);

        return [
            'version' => PHP_VERSION,
            'version_id' => PHP_VERSION_ID,
            'zend_version' => zend_version(),
            'ini_path' => php_ini_loaded_file() ?: null,
            'extensions' => $extensions,
            'extensions_count' => count(get_loaded_extensions()),
            'max_execution_time' => (int) ini_get('max_execution_time'),
            'max_input_time' => (int) ini_get('max_input_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'opcache' => $this->getOpcacheInfo(),
        ];
    }

    private function getApplicationInfo(): array
    {
        return [
            'name' => config('app.name'),
            'env' => config('app.env'),
            'debug' => (bool) config('app.debug'),
            'url' => config('app.url'),
            'laravel_version' => app()->version(),
            'locale' => config('app.locale'),
            'fallback_locale' => config('app.fallback_locale'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_connection' => config('queue.default'),
            'filesystem_disk' => config('filesystems.default'),
            'broadcast_driver' => config('broadcasting.default'),
            'mail_mailer' => config('mail.default'),
            'maintenance_mode' => app()->isDownForMaintenance(),
            'optimizations' => [
                'config_cached' => File::exists(base_path('bootstrap/cache/config.php')),
                'routes_cached' => File::exists(base_path('bootstrap/cache/routes-v7.php')),
                'events_cached' => File::exists(base_path('bootstrap/cache/events.php')),
            ],
            'composer' => $this->getComposerInfo(),
        ];
    }

    private function getDatabaseInfo(): array
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}", []);
        $status = 'unknown';
        $version = null;
        $sizeBytes = 0;
        $tableCount = 0;
        $connectionCount = null;
        $topTables = [];
        $mysqlStatus = [];
        $uptimeSeconds = null;

        try {
            DB::connection()->getPdo();
            $status = 'connected';
            $version = DB::selectOne('SELECT VERSION() as version')?->version;

            if ($driver === 'mysql') {
                $dbName = $connection['database'] ?? null;
                if ($dbName) {
                    $sizeRow = DB::selectOne(
                        'SELECT SUM(data_length + index_length) AS size, SUM(data_free) AS free_space
                         FROM information_schema.tables WHERE table_schema = ?',
                        [$dbName]
                    );
                    $sizeBytes = (int) ($sizeRow->size ?? 0);

                    $tableCount = (int) DB::table('information_schema.tables')
                        ->where('table_schema', $dbName)
                        ->count();

                    $topTables = DB::select(
                        'SELECT table_name AS name,
                                ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                                table_rows AS row_count
                         FROM information_schema.tables
                         WHERE table_schema = ?
                         ORDER BY (data_length + index_length) DESC
                         LIMIT 10',
                        [$dbName]
                    );

                    $topTables = array_map(fn ($row) => [
                        'name' => $row->name,
                        'size_mb' => (float) $row->size_mb,
                        'size_human' => $this->formatBytes((int) ($row->size_mb * 1048576)),
                        'row_count' => (int) $row->row_count,
                    ], $topTables);

                    $statusVars = ['Threads_connected', 'Threads_running', 'Queries', 'Slow_queries', 'Uptime', 'Max_used_connections'];
                    foreach ($statusVars as $var) {
                        $row = DB::selectOne('SHOW GLOBAL STATUS LIKE ?', [$var]);
                        if ($row && isset($row->Value)) {
                            $mysqlStatus[$var] = (int) $row->Value;
                        }
                    }

                    $connectionCount = $mysqlStatus['Threads_connected'] ?? null;
                    $uptimeSeconds = $mysqlStatus['Uptime'] ?? null;
                }
            }
        } catch (\Throwable $e) {
            $status = 'error';
        }

        return [
            'driver' => $driver,
            'host' => $connection['host'] ?? null,
            'port' => $connection['port'] ?? null,
            'database' => $connection['database'] ?? null,
            'status' => $status,
            'version' => $version,
            'size_bytes' => $sizeBytes,
            'size_mb' => round($sizeBytes / 1048576, 2),
            'size_human' => $this->formatBytes($sizeBytes),
            'tables_count' => $tableCount,
            'connections' => $connectionCount,
            'uptime_seconds' => $uptimeSeconds,
            'uptime_human' => $this->formatDuration($uptimeSeconds),
            'top_tables' => $topTables,
            'mysql_status' => $mysqlStatus,
            'chart' => [
                'labels' => array_map(fn ($t) => $t['name'], $topTables),
                'values' => array_map(fn ($t) => $t['size_mb'], $topTables),
            ],
        ];
    }

    private function getCacheInfo(): array
    {
        $driver = config('cache.default');
        $status = 'unknown';
        $latencyMs = null;

        try {
            $key = 'system_resource_cache_probe:' . Str::random(8);
            $started = microtime(true);
            Cache::put($key, 'ok', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            $latencyMs = round((microtime(true) - $started) * 1000, 2);
            $status = $value === 'ok' ? 'healthy' : 'degraded';
        } catch (\Throwable $e) {
            $status = 'error';
        }

        return [
            'driver' => $driver,
            'status' => $status,
            'latency_ms' => $latencyMs,
            'prefix' => config('cache.prefix'),
            'stores' => array_keys(config('cache.stores', [])),
        ];
    }

    private function getRedisInfo(): array
    {
        if (!extension_loaded('redis') && config('cache.default') !== 'redis' && config('queue.default') !== 'redis') {
            return ['available' => false];
        }

        try {
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $started = microtime(true);
            $pong = $redis->ping();
            $latencyMs = round((microtime(true) - $started) * 1000, 2);

            $info = $redis->info();
            $memory = $info['Memory'] ?? $info['used_memory_human'] ?? null;

            return [
                'available' => true,
                'status' => $pong ? 'healthy' : 'degraded',
                'latency_ms' => $latencyMs,
                'version' => $info['redis_version'] ?? ($info['Server']['redis_version'] ?? null),
                'used_memory_human' => is_array($memory) ? ($memory['used_memory_human'] ?? null) : $info['used_memory_human'] ?? null,
                'connected_clients' => (int) ($info['connected_clients'] ?? ($info['Clients']['connected_clients'] ?? 0)),
                'uptime_seconds' => (int) ($info['uptime_in_seconds'] ?? ($info['Server']['uptime_in_seconds'] ?? 0)),
            ];
        } catch (\Throwable $e) {
            return [
                'available' => true,
                'status' => 'error',
                'error' => 'اتصال Redis برقرار نشد',
            ];
        }
    }

    private function getQueueInfo(): array
    {
        $connection = config('queue.default');
        $pending = null;
        $failed = null;
        $batches = null;
        $recentFailed = [];

        try {
            if (Schema::hasTable('jobs')) {
                $pending = (int) DB::table('jobs')->count();
            }
            if (Schema::hasTable('failed_jobs')) {
                $failed = (int) DB::table('failed_jobs')->count();
                $recentFailed = DB::table('failed_jobs')
                    ->orderByDesc('failed_at')
                    ->limit(5)
                    ->get(['id', 'queue', 'failed_at'])
                    ->map(fn ($row) => [
                        'id' => $row->id,
                        'queue' => $row->queue,
                        'failed_at' => $row->failed_at,
                    ])
                    ->all();
            }
            if (Schema::hasTable('job_batches')) {
                $batches = (int) DB::table('job_batches')->count();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $status = 'healthy';
        if ($failed !== null && $failed > 0) {
            $status = 'warning';
        }
        if ($pending !== null && $pending > 100) {
            $status = 'warning';
        }

        return [
            'connection' => $connection,
            'size' => $this->safeQueueSize(),
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'job_batches' => $batches,
            'recent_failed' => $recentFailed,
            'status' => $status,
        ];
    }

    private function getLogsInfo(): array
    {
        $logPath = storage_path('logs/laravel.log');
        $exists = File::exists($logPath);
        $size = $exists ? File::size($logPath) : 0;
        $modified = $exists ? File::lastModified($logPath) : null;

        $errorCount = 0;
        $warningCount = 0;
        if ($exists && $size > 0 && $size < 50 * 1048576) {
            $lines = $this->tailFile($logPath, 500);
            foreach ($lines as $line) {
                if (str_contains($line, '.ERROR:')) {
                    $errorCount++;
                }
                if (str_contains($line, '.WARNING:')) {
                    $warningCount++;
                }
            }
        }

        return [
            'laravel' => [
                'exists' => $exists,
                'path' => $logPath,
                'size_bytes' => $size,
                'size_human' => $this->formatBytes($size),
                'modified_at' => $modified ? date('c', $modified) : null,
                'error_lines_recent' => $errorCount,
                'warning_lines_recent' => $warningCount,
                'error_lines_24h' => $errorCount,
            ],
            'channels' => config('logging.channels') ? array_keys(config('logging.channels')) : [],
        ];
    }

    private function getSecurityAudit(): array
    {
        $appUrl = config('app.url', '');
        $checks = [
            'debug_off_in_production' => !(config('app.debug') && config('app.env') === 'production'),
            'app_key_set' => !empty(config('app.key')) && config('app.key') !== 'base64:',
            'https' => str_starts_with($appUrl, 'https://'),
            'storage_not_public' => !is_link(public_path('storage')) || true,
            'env_not_exposed' => !File::exists(public_path('.env')),
        ];

        $passed = count(array_filter($checks));
        $total = count($checks);
        $score = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

        return [
            'score' => $score,
            'checks' => $checks,
            'items' => collect($checks)->map(fn ($ok, $key) => [
                'key' => $key,
                'label' => $this->securityLabel($key),
                'ok' => $ok,
                'status' => $ok ? 'healthy' : 'warning',
            ])->values()->all(),
        ];
    }

    private function getApplicationMetrics(): array
    {
        return Cache::remember('admin:system_app_metrics', self::METRICS_CACHE_TTL, function () {
            $metrics = [];

            $tables = [
                'users' => 'users',
                'courses' => 'courses',
                'payments' => 'payments',
                'comments' => 'comments',
            ];

            foreach ($tables as $key => $table) {
                try {
                    if (Schema::hasTable($table)) {
                        $metrics[$key] = (int) DB::table($table)->count();
                    }
                } catch (\Throwable $e) {
                    $metrics[$key] = null;
                }
            }

            try {
                $metrics['migrations'] = Schema::hasTable('migrations')
                    ? (int) DB::table('migrations')->count()
                    : null;
            } catch (\Throwable $e) {
                $metrics['migrations'] = null;
            }

            try {
                if (config('session.driver') === 'database' && Schema::hasTable('sessions')) {
                    $metrics['active_sessions'] = (int) DB::table('sessions')->count();
                }
            } catch (\Throwable $e) {
                // ignore
            }

            return $metrics;
        });
    }

    private function getServicesHealth(array $database, array $cache): array
    {
        $checks = [
            'database' => ($database['status'] ?? '') === 'connected',
            'cache' => ($cache['status'] ?? '') === 'healthy',
            'storage' => is_writable(storage_path()),
            'logs' => is_writable(storage_path('logs')),
            'bootstrap_cache' => is_writable(base_path('bootstrap/cache')),
        ];

        try {
            $disk = Storage::disk(config('filesystems.default'));
            $probe = 'system_resource_probe_' . Str::random(8);
            $disk->put($probe, 'ok');
            $checks['filesystem'] = $disk->get($probe) === 'ok';
            $disk->delete($probe);
        } catch (\Throwable $e) {
            $checks['filesystem'] = false;
        }

        if (extension_loaded('redis') || config('cache.default') === 'redis') {
            $redis = $this->getRedisInfo();
            $checks['redis'] = ($redis['status'] ?? '') === 'healthy';
        }

        $healthy = count(array_filter($checks));
        $total = count($checks);

        return [
            'checks' => collect($checks)->map(fn ($ok, $name) => [
                'name' => $name,
                'label' => $this->serviceLabel($name),
                'status' => $ok ? 'healthy' : 'error',
                'ok' => $ok,
            ])->values()->all(),
            'summary' => [
                'healthy' => $healthy,
                'total' => $total,
                'score_percent' => $total > 0 ? round(($healthy / $total) * 100, 1) : 0,
            ],
        ];
    }

    private function getNetworkInfo(): array
    {
        $ips = [];
        if (function_exists('gethostbynamel')) {
            $hostname = gethostname();
            $resolved = $hostname ? @gethostbynamel($hostname) : false;
            if (is_array($resolved)) {
                $ips = $resolved;
            }
        }

        return [
            'hostname' => gethostname() ?: null,
            'ips' => $ips,
            'server_addr' => $_SERVER['SERVER_ADDR'] ?? null,
            'server_port' => $_SERVER['SERVER_PORT'] ?? null,
        ];
    }

    private function getComposerInfo(): array
    {
        $lockPath = base_path('composer.lock');
        if (!File::exists($lockPath)) {
            return ['packages' => null];
        }

        try {
            $lock = json_decode(File::get($lockPath), true, 512, JSON_THROW_ON_ERROR);

            return [
                'packages' => count($lock['packages'] ?? []),
                'packages_dev' => count($lock['packages-dev'] ?? []),
            ];
        } catch (\Throwable $e) {
            return ['packages' => null];
        }
    }

    private function getLargestLogFiles(): array
    {
        $logDir = storage_path('logs');
        if (!is_dir($logDir)) {
            return [];
        }

        $files = [];
        foreach (glob($logDir . '/*.log') ?: [] as $file) {
            $files[] = [
                'name' => basename($file),
                'size_bytes' => filesize($file),
                'size_human' => $this->formatBytes((int) filesize($file)),
                'modified_at' => date('c', filemtime($file)),
            ];
        }

        usort($files, fn ($a, $b) => $b['size_bytes'] <=> $a['size_bytes']);

        return array_slice($files, 0, 5);
    }

    private function tailFile(string $path, int $lines): array
    {
        $handle = @fopen($path, 'rb');
        if (!$handle) {
            return [];
        }

        fseek($handle, -1, SEEK_END);
        $buffer = '';
        $lineCount = 0;
        $result = [];

        while ($lineCount < $lines && ftell($handle) > 0) {
            $char = fgetc($handle);
            if ($char === "\n") {
                if ($buffer !== '') {
                    $result[] = strrev($buffer);
                    $buffer = '';
                    $lineCount++;
                }
            } else {
                $buffer .= $char;
            }
            fseek($handle, -2, SEEK_CUR);
        }

        if ($buffer !== '') {
            $result[] = strrev($buffer);
        }

        fclose($handle);

        return array_reverse($result);
    }

    private function securityLabel(string $key): string
    {
        return match ($key) {
            'debug_off_in_production' => 'Debug در production غیرفعال',
            'app_key_set' => 'کلید اپلیکیشن تنظیم شده',
            'https' => 'HTTPS فعال',
            'storage_not_public' => 'Storage امن',
            'env_not_exposed' => 'فایل .env در public نیست',
            default => $key,
        };
    }

    private function serviceLabel(string $name): string
    {
        return match ($name) {
            'database' => 'پایگاه داده',
            'cache' => 'کش',
            'redis' => 'Redis',
            'storage' => 'فضای storage',
            'logs' => 'فایل‌های لاگ',
            'bootstrap_cache' => 'کش بوت‌استرپ',
            'filesystem' => 'سیستم فایل',
            default => $name,
        };
    }

    private function healthGrade(float $score): string
    {
        if ($score >= 90) {
            return 'A';
        }
        if ($score >= 80) {
            return 'B';
        }
        if ($score >= 70) {
            return 'C';
        }
        if ($score >= 60) {
            return 'D';
        }

        return 'F';
    }

    private function healthStatus(float $score): string
    {
        if ($score >= 85) {
            return 'healthy';
        }
        if ($score >= 65) {
            return 'warning';
        }

        return 'critical';
    }

    private function healthLabel(float $score): string
    {
        if ($score >= 90) {
            return 'عالی';
        }
        if ($score >= 75) {
            return 'خوب';
        }
        if ($score >= 60) {
            return 'متوسط';
        }

        return 'نیاز به توجه';
    }

    private function getUptimeSeconds(): ?int
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/uptime')) {
            $parts = explode(' ', trim((string) file_get_contents('/proc/uptime')));

            return isset($parts[0]) ? (int) floor((float) $parts[0]) : null;
        }

        return null;
    }

    private function getProcessCount(): ?int
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/stat')) {
            return count(glob('/proc/[0-9]*') ?: []);
        }

        return null;
    }

    private function getCpuModel(): ?string
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/cpuinfo')) {
            $content = file_get_contents('/proc/cpuinfo');
            if (preg_match('/model name\s*:\s*(.+)/i', $content, $matches)) {
                return trim($matches[1]);
            }
        }

        $output = @shell_exec('wmic cpu get name /value 2>NUL');
        if ($output && preg_match('/Name=(.+)/', $output, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    private function getCpuCores(): int
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/cpuinfo')) {
            $content = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $content, $matches);

            return max(1, count($matches[0]));
        }

        $envCores = (int) getenv('NUMBER_OF_PROCESSORS');

        return $envCores > 0 ? $envCores : 1;
    }

    private function getLoadAverage(): ?array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (is_array($load) && count($load) >= 3) {
                return [
                    '1min' => round($load[0], 2),
                    '5min' => round($load[1], 2),
                    '15min' => round($load[2], 2),
                ];
            }
        }

        return null;
    }

    private function getWindowsCpuUsage(): ?float
    {
        $output = @shell_exec('wmic cpu get loadpercentage /value 2>NUL');
        if ($output && preg_match('/LoadPercentage=(\d+)/', $output, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function getSystemMemory(): array
    {
        if (PHP_OS_FAMILY === 'Linux' && is_readable('/proc/meminfo')) {
            $info = [];
            foreach (file('/proc/meminfo', FILE_IGNORE_NEW_LINES) as $line) {
                if (!str_contains($line, ':')) {
                    continue;
                }
                [$key, $value] = array_map('trim', explode(':', $line, 2));
                $info[$key] = (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT) * 1024;
            }

            $total = $info['MemTotal'] ?? 0;
            $available = $info['MemAvailable'] ?? ($info['MemFree'] ?? 0);
            $used = max(0, $total - $available);
            $usagePercent = $total > 0 ? round(($used / $total) * 100, 1) : null;

            return [
                'total_bytes' => $total,
                'used_bytes' => $used,
                'available_bytes' => $available,
                'total_human' => $this->formatBytes($total),
                'used_human' => $this->formatBytes($used),
                'available_human' => $this->formatBytes($available),
                'usage_percent' => $usagePercent,
                'status' => $this->usageStatus($usagePercent),
                'source' => 'proc_meminfo',
            ];
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $total = $this->getWindowsMemoryTotal();
            if ($total > 0) {
                $available = $this->getWindowsMemoryAvailable();
                $used = max(0, $total - $available);
                $usagePercent = round(($used / $total) * 100, 1);

                return [
                    'total_bytes' => $total,
                    'used_bytes' => $used,
                    'available_bytes' => $available,
                    'total_human' => $this->formatBytes($total),
                    'used_human' => $this->formatBytes($used),
                    'available_human' => $this->formatBytes($available),
                    'usage_percent' => $usagePercent,
                    'status' => $this->usageStatus($usagePercent),
                    'source' => 'wmic',
                ];
            }
        }

        return [
            'total_bytes' => null,
            'used_bytes' => null,
            'available_bytes' => null,
            'total_human' => null,
            'used_human' => null,
            'available_human' => null,
            'usage_percent' => null,
            'status' => 'unknown',
            'source' => 'unavailable',
        ];
    }

    private function getWindowsMemoryTotal(): int
    {
        $output = @shell_exec('wmic OS get TotalVisibleMemorySize /Value 2>NUL');
        if (!$output || !preg_match('/TotalVisibleMemorySize=(\d+)/', $output, $matches)) {
            return 0;
        }

        return (int) $matches[1] * 1024;
    }

    private function getWindowsMemoryAvailable(): int
    {
        $output = @shell_exec('wmic OS get FreePhysicalMemory /Value 2>NUL');
        if (!$output || !preg_match('/FreePhysicalMemory=(\d+)/', $output, $matches)) {
            return 0;
        }

        return (int) $matches[1] * 1024;
    }

    private function getPathDiskUsage(string $path): array
    {
        $total = @disk_total_space($path);
        $free = @disk_free_space($path);

        if ($total === false || $free === false) {
            return [
                'path' => $path,
                'total_bytes' => null,
                'used_bytes' => null,
                'free_bytes' => null,
                'usage_percent' => null,
                'status' => 'unknown',
            ];
        }

        $used = max(0, $total - $free);
        $usagePercent = $total > 0 ? round(($used / $total) * 100, 1) : null;

        return [
            'path' => $path,
            'total_bytes' => (int) $total,
            'used_bytes' => (int) $used,
            'free_bytes' => (int) $free,
            'total_human' => $this->formatBytes((int) $total),
            'used_human' => $this->formatBytes((int) $used),
            'free_human' => $this->formatBytes((int) $free),
            'usage_percent' => $usagePercent,
            'status' => $this->usageStatus($usagePercent),
        ];
    }

    private function getDirectorySize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        return Cache::remember(
            'admin:dir_size:' . md5($path),
            self::DIR_SIZE_CACHE_TTL,
            function () use ($path) {
                $size = 0;

                try {
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
                    );

                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $size += $file->getSize();
                        }
                    }
                } catch (\Throwable $e) {
                    return 0;
                }

                return $size;
            }
        );
    }

    private function getOpcacheInfo(): array
    {
        if (!function_exists('opcache_get_status')) {
            return ['enabled' => false];
        }

        $status = @opcache_get_status(false);
        if (!is_array($status)) {
            return ['enabled' => false];
        }

        $memory = $status['memory_usage'] ?? [];
        $stats = $status['opcache_statistics'] ?? [];
        $used = (int) ($memory['used_memory'] ?? 0);
        $free = (int) ($memory['free_memory'] ?? 0);
        $total = $used + $free;

        return [
            'enabled' => (bool) ($status['opcache_enabled'] ?? false),
            'hit_rate' => isset($stats['opcache_hit_rate']) ? round((float) $stats['opcache_hit_rate'], 2) : null,
            'cached_scripts' => (int) ($stats['num_cached_scripts'] ?? 0),
            'memory_used_human' => $this->formatBytes($used),
            'memory_total_human' => $this->formatBytes($total),
            'memory_usage_percent' => $total > 0 ? round(($used / $total) * 100, 1) : null,
        ];
    }

    private function safeQueueSize(): ?int
    {
        try {
            return Queue::size();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function usageStatus(?float $percent): string
    {
        if ($percent === null) {
            return 'unknown';
        }
        if ($percent >= 90) {
            return 'critical';
        }
        if ($percent >= 75) {
            return 'warning';
        }

        return 'healthy';
    }

    private function parseSize(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        if (in_array($unit, ['g', 'm', 'k'], true)) {
            $number = (float) substr($value, 0, -1);
        } else {
            $unit = 'b';
        }

        return (int) round(match ($unit) {
            'g' => $number * 1073741824,
            'm' => $number * 1048576,
            'k' => $number * 1024,
            default => $number,
        });
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }

    private function formatDuration(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' روز';
        }
        if ($hours > 0) {
            $parts[] = $hours . ' ساعت';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' دقیقه';
        }

        return $parts !== [] ? implode(' و ', $parts) : 'کمتر از یک دقیقه';
    }
}
