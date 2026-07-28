<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LaravelLogService
{
    private const MAX_ANALYSIS_BYTES = 80 * 1048576;

    private const MAX_LIST_SCAN_BYTES = 40 * 1048576;

    private const ENTRY_PATTERN = '/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+([\w.-]+)\.(\w+):\s*(.*)$/';

    private const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    public function listFiles(): array
    {
        $dir = storage_path('logs');

        if (!File::isDirectory($dir)) {
            return [];
        }

        $files = [];
        foreach (File::files($dir) as $file) {
            if (!Str::endsWith($file->getFilename(), '.log')) {
                continue;
            }

            $name = $file->getFilename();
            $size = $file->getSize();
            $modified = $file->getMTime();

            $files[] = [
                'name' => $name,
                'size_bytes' => $size,
                'size_human' => $this->formatBytes($size),
                'modified_at' => date('c', $modified),
                'is_daily' => (bool) preg_match('/^laravel-\d{4}-\d{2}-\d{2}\.log$/', $name),
                'is_current' => $name === 'laravel.log',
            ];
        }

        usort($files, function ($a, $b) {
            if ($a['is_current'] !== $b['is_current']) {
                return $a['is_current'] ? -1 : 1;
            }

            return strcmp($b['modified_at'], $a['modified_at']);
        });

        return $files;
    }

    public function analyze(string $filename, ?string $from = null, ?string $to = null): array
    {
        $path = $this->resolvePath($filename);
        if (!$path) {
            throw new \InvalidArgumentException('فایل لاگ معتبر نیست.');
        }

        $meta = $this->fileMeta($path, $filename);
        $entries = $this->parseEntries($path, self::MAX_ANALYSIS_BYTES);

        if ($from) {
            $entries = array_filter($entries, fn ($e) => $e['datetime'] >= $from);
        }
        if ($to) {
            $entries = array_filter($entries, fn ($e) => $e['datetime'] <= $to);
        }
        $entries = array_values($entries);

        $levelCounts = array_fill_keys(self::LEVELS, 0);
        $channelCounts = [];
        $hourly = [];
        $daily = [];
        $exceptionCounts = [];
        $contextKeys = [];

        foreach ($entries as $entry) {
            $level = strtolower($entry['level']);
            if (isset($levelCounts[$level])) {
                $levelCounts[$level]++;
            }

            $channel = $entry['channel'] ?? 'unknown';
            $channelCounts[$channel] = ($channelCounts[$channel] ?? 0) + 1;

            $hourKey = substr($entry['datetime'], 0, 13);
            $dayKey = substr($entry['datetime'], 0, 10);

            if (!isset($hourly[$hourKey])) {
                $hourly[$hourKey] = ['total' => 0, 'error' => 0, 'warning' => 0];
            }
            $hourly[$hourKey]['total']++;
            if (in_array($level, ['error', 'critical', 'alert', 'emergency'], true)) {
                $hourly[$hourKey]['error']++;
            }
            if ($level === 'warning') {
                $hourly[$hourKey]['warning']++;
            }

            $daily[$dayKey] = ($daily[$dayKey] ?? 0) + 1;

            $exceptionClass = $this->extractExceptionClass($entry['message'], $entry['stack']);
            if ($exceptionClass) {
                $exceptionCounts[$exceptionClass] = ($exceptionCounts[$exceptionClass] ?? 0) + 1;
            }

            if (!empty($entry['context']) && is_array($entry['context'])) {
                foreach (array_keys($entry['context']) as $key) {
                    $contextKeys[$key] = ($contextKeys[$key] ?? 0) + 1;
                }
            }
        }

        $total = count($entries);
        $errorCount = $levelCounts['error'] + $levelCounts['critical'] + $levelCounts['alert'] + $levelCounts['emergency'];
        $warningCount = $levelCounts['warning'];

        $sortedHours = $this->sortHourlyBuckets($hourly);
        $sortedDays = $this->sortDailyBuckets($daily);

        $topExceptions = collect($exceptionCounts)
            ->sortDesc()
            ->take(10)
            ->map(fn ($count, $class) => ['class' => $class, 'count' => $count])
            ->values()
            ->all();

        $topChannels = collect($channelCounts)
            ->sortDesc()
            ->take(8)
            ->map(fn ($count, $channel) => ['channel' => $channel, 'count' => $count])
            ->values()
            ->all();

        $topContextKeys = collect($contextKeys)
            ->sortDesc()
            ->take(8)
            ->map(fn ($count, $key) => ['key' => $key, 'count' => $count])
            ->values()
            ->all();

        $health = $this->buildHealth($total, $errorCount, $warningCount);

        return array_merge($meta, [
            'analyzed_entries' => $total,
            'levels' => $levelCounts,
            'totals' => [
                'all' => $total,
                'errors' => $errorCount,
                'warnings' => $warningCount,
                'info' => $levelCounts['info'],
                'debug' => $levelCounts['debug'],
                'notice' => $levelCounts['notice'],
            ],
            'error_rate_percent' => $total > 0 ? round(($errorCount / $total) * 100, 2) : 0,
            'warning_rate_percent' => $total > 0 ? round(($warningCount / $total) * 100, 2) : 0,
            'health' => $health,
            'charts' => [
                'levels' => [
                    'labels' => ['Emergency', 'Alert', 'Critical', 'Error', 'Warning', 'Notice', 'Info', 'Debug'],
                    'data' => [
                        $levelCounts['emergency'],
                        $levelCounts['alert'],
                        $levelCounts['critical'],
                        $levelCounts['error'],
                        $levelCounts['warning'],
                        $levelCounts['notice'],
                        $levelCounts['info'],
                        $levelCounts['debug'],
                    ],
                    'colors' => ['#7f1d1d', '#991b1b', '#dc2626', '#ef4444', '#f59e0b', '#3b82f6', '#10b981', '#6b7280'],
                ],
                'hourly' => [
                    'labels' => array_column($sortedHours, 'label'),
                    'total' => array_column($sortedHours, 'total'),
                    'errors' => array_column($sortedHours, 'errors'),
                    'warnings' => array_column($sortedHours, 'warnings'),
                ],
                'daily' => [
                    'labels' => array_column($sortedDays, 'label'),
                    'data' => array_column($sortedDays, 'count'),
                ],
                'channels' => [
                    'labels' => array_column($topChannels, 'channel'),
                    'data' => array_column($topChannels, 'count'),
                    'colors' => ['#3b82f6', '#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#ef4444', '#ec4899'],
                ],
                'exceptions' => [
                    'labels' => array_map(fn ($e) => Str::limit($e['class'], 40), $topExceptions),
                    'data' => array_column($topExceptions, 'count'),
                ],
            ],
            'top_exceptions' => $topExceptions,
            'top_channels' => $topChannels,
            'top_context_keys' => $topContextKeys,
            'collected_at' => now()->toIso8601String(),
        ]);
    }

    public function listEntries(array $filters): array
    {
        $filename = $filters['file'] ?? 'laravel.log';
        $path = $this->resolvePath($filename);
        if (!$path) {
            throw new \InvalidArgumentException('فایل لاگ معتبر نیست.');
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int) ($filters['per_page'] ?? 25)));
        $level = isset($filters['level']) ? strtolower((string) $filters['level']) : null;
        $search = isset($filters['search']) ? trim((string) $filters['search']) : null;
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        $entries = $this->parseEntries($path, self::MAX_LIST_SCAN_BYTES);

        $filtered = [];
        foreach ($entries as $index => $entry) {
            if ($level && strtolower($entry['level']) !== $level) {
                continue;
            }
            if ($from && $entry['datetime'] < $from) {
                continue;
            }
            if ($to && $entry['datetime'] > $to) {
                continue;
            }
            if ($search) {
                $haystack = strtolower($entry['message'] . ' ' . $entry['stack'] . ' ' . json_encode($entry['context'] ?? []));
                if (!str_contains($haystack, strtolower($search))) {
                    continue;
                }
            }
            $entry['id'] = $index;
            $filtered[] = $entry;
        }

        // Most recent first
        $filtered = array_reverse($filtered);
        $total = count($filtered);
        $offset = ($page - 1) * $perPage;
        $slice = array_slice($filtered, $offset, $perPage);

        return [
            'file' => $filename,
            'data' => $this->summarizeEntries($slice),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ];
    }

    public function getEntry(string $filename, int $id): ?array
    {
        $path = $this->resolvePath($filename);
        if (!$path) {
            throw new \InvalidArgumentException('فایل لاگ معتبر نیست.');
        }

        $entries = $this->parseEntries($path, self::MAX_LIST_SCAN_BYTES);
        if (!isset($entries[$id])) {
            return null;
        }

        $entry = $entries[$id];
        $entry['id'] = $id;

        return $entry;
    }

    public function downloadPath(string $filename): ?string
    {
        return $this->resolvePath($filename);
    }

    public function clearFile(string $filename): bool
    {
        $path = $this->resolvePath($filename);
        if (!$path) {
            throw new \InvalidArgumentException('فایل لاگ معتبر نیست.');
        }

        File::put($path, '');

        return true;
    }

    public function deleteFile(string $filename): bool
    {
        if ($filename === 'laravel.log') {
            throw new \InvalidArgumentException('حذف فایل اصلی laravel.log مجاز نیست؛ از پاک‌سازی استفاده کنید.');
        }

        $path = $this->resolvePath($filename);
        if (!$path) {
            throw new \InvalidArgumentException('فایل لاگ معتبر نیست.');
        }

        return File::delete($path);
    }

    private function resolvePath(string $filename): ?string
    {
        $filename = basename($filename);
        if (!Str::endsWith($filename, '.log') || str_contains($filename, '..')) {
            return null;
        }

        $path = storage_path('logs/' . $filename);
        if (!File::exists($path) || !File::isFile($path)) {
            return null;
        }

        $real = realpath($path);
        $logsDir = realpath(storage_path('logs'));

        if (!$real || !$logsDir || !Str::startsWith($real, $logsDir)) {
            return null;
        }

        return $real;
    }

    private function fileMeta(string $path, string $filename): array
    {
        $size = File::size($path);
        $modified = File::lastModified($path);

        return [
            'file' => $filename,
            'size_bytes' => $size,
            'size_human' => $this->formatBytes($size),
            'modified_at' => date('c', $modified),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseEntries(string $path, int $maxBytes): array
    {
        $size = File::size($path);
        $readFrom = $size > $maxBytes ? $size - $maxBytes : 0;

        $handle = fopen($path, 'rb');
        if (!$handle) {
            return [];
        }

        if ($readFrom > 0) {
            fseek($handle, $readFrom);
            fgets($handle);
        }

        $entries = [];
        $current = null;
        $index = 0;

        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");

            if (preg_match(self::ENTRY_PATTERN, $line, $m)) {
                if ($current !== null) {
                    $entries[$index] = $this->finalizeEntry($current);
                    $index++;
                }

                $current = [
                    'datetime' => $m[1],
                    'channel' => $m[2],
                    'level' => strtoupper($m[3]),
                    'message_line' => $m[4],
                    'stack_lines' => [],
                ];
            } elseif ($current !== null) {
                $current['stack_lines'][] = $line;
            }
        }

        if ($current !== null) {
            $entries[$index] = $this->finalizeEntry($current);
        }

        fclose($handle);

        return $entries;
    }

    private function finalizeEntry(array $raw): array
    {
        $messageLine = $raw['message_line'];
        $context = null;
        $message = $messageLine;

        if (preg_match('/^(.*?)\s+(\{.*\})\s*$/', $messageLine, $m)) {
            $decoded = json_decode($m[2], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $message = trim($m[1]);
                $context = $decoded;
            }
        }

        $stack = implode("\n", $raw['stack_lines']);

        return [
            'datetime' => $raw['datetime'],
            'channel' => $raw['channel'],
            'level' => $raw['level'],
            'message' => $message,
            'context' => $context,
            'stack' => $stack,
            'has_stack' => $stack !== '',
            'exception_class' => $this->extractExceptionClass($message, $stack),
        ];
    }

    private function summarizeEntries(array $entries): array
    {
        return array_map(function ($entry) {
            return [
                'id' => $entry['id'],
                'datetime' => $entry['datetime'],
                'channel' => $entry['channel'],
                'level' => $entry['level'],
                'message' => Str::limit($entry['message'], 200),
                'has_stack' => $entry['has_stack'],
                'exception_class' => $entry['exception_class'],
                'context_preview' => $entry['context'] ? Str::limit(json_encode($entry['context'], JSON_UNESCAPED_UNICODE), 120) : null,
            ];
        }, $entries);
    }

    private function extractExceptionClass(string $message, string $stack): ?string
    {
        if (preg_match('/^([A-Za-z0-9_\\\\]+Exception|Error|Throwable)(?::|$)/', $message, $m)) {
            return $m[1];
        }

        if (preg_match('/^([A-Za-z0-9_\\\\]+Exception|Error|Throwable):/', $stack, $m)) {
            return $m[1];
        }

        if (preg_match('/\[previous exception\]\s*\[object\]\s*\(([^)]+)\)/', $stack, $m)) {
            return $m[1];
        }

        return null;
    }

    private function buildHealth(int $total, int $errors, int $warnings): array
    {
        if ($total === 0) {
            return ['score' => 100, 'status' => 'healthy', 'label' => 'بدون لاگ در بازه تحلیل', 'grade' => 'A'];
        }

        $errorRate = ($errors / $total) * 100;
        $warningRate = ($warnings / $total) * 100;
        $penalty = min(70, $errorRate * 2 + $warningRate * 0.5);
        $score = max(0, (int) round(100 - $penalty));

        if ($score >= 85) {
            return ['score' => $score, 'status' => 'healthy', 'label' => 'وضعیت لاگ سالم', 'grade' => 'A'];
        }
        if ($score >= 70) {
            return ['score' => $score, 'status' => 'warning', 'label' => 'هشدارهای قابل توجه', 'grade' => 'B'];
        }
        if ($score >= 50) {
            return ['score' => $score, 'status' => 'warning', 'label' => 'خطاهای زیاد در لاگ', 'grade' => 'C'];
        }

        return ['score' => $score, 'status' => 'critical', 'label' => 'وضعیت بحرانی لاگ‌ها', 'grade' => 'F'];
    }

    private function sortHourlyBuckets(array $hourly): array
    {
        ksort($hourly);
        $result = [];
        foreach ($hourly as $key => $counts) {
            $result[] = [
                'label' => substr($key, 11) . ':00',
                'total' => $counts['total'],
                'errors' => $counts['error'],
                'warnings' => $counts['warning'],
            ];
        }

        return $result;
    }

    private function sortDailyBuckets(array $daily): array
    {
        ksort($daily);
        $result = [];
        foreach ($daily as $day => $count) {
            $result[] = ['label' => $day, 'count' => $count];
        }

        return $result;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
