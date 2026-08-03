<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Best-effort media cleanup after a messenger media message is deleted for everyone.
 * Supports private path-based meta and legacy public URLs.
 */
class DeleteMessengerMedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public readonly ?array $meta,
        public readonly string $type = 'photo',
    ) {}

    public function handle(): void
    {
        if (! is_array($this->meta) || $this->meta === []) {
            return;
        }

        $diskName = $this->meta['disk'] ?? config('messenger.media.disk', 'static');
        $disk = Storage::disk($diskName);
        $paths = [];

        foreach (['path', 'thumb_path', 'cover_path'] as $key) {
            $val = $this->meta[$key] ?? null;
            if (is_string($val) && $val !== '') {
                $paths[] = ltrim(str_replace('\\', '/', $val), '/');
            }
        }

        $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url", ''), '/');
        foreach (['url', 'thumb_url', 'cover_url'] as $key) {
            $val = $this->meta[$key] ?? null;
            if (is_string($val) && $val !== '' && ! str_starts_with($val, 'blob:') && ! str_contains($val, '/api/messenger/media/')) {
                $mapped = $this->urlToDiskPath($val, $baseUrl);
                if ($mapped) {
                    $paths[] = $mapped;
                }
            }
        }

        foreach (array_unique($paths) as $path) {
            if (! $this->isAllowedMediaPath($path)) {
                continue;
            }
            try {
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            } catch (\Throwable $e) {
                Log::warning('messenger.media_delete_failed', [
                    'path' => $path,
                    'type' => $this->type,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function isAllowedMediaPath(string $path): bool
    {
        $folder = trim((string) config('messenger.media.folder', 'images/messenger/chats'), '/');
        $private = trim((string) config('messenger.media.private_folder', 'private/messenger'), '/');
        foreach ([$folder, $private, 'images/messenger/wallpapers'] as $prefix) {
            if ($prefix !== '' && str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    protected function urlToDiskPath(string $url, string $baseUrl): ?string
    {
        $normalized = strtok($url, '?') ?: $url;
        if ($baseUrl !== '' && (Str::startsWith($normalized, $baseUrl.'/') || $normalized === $baseUrl)) {
            $path = ltrim(substr($normalized, strlen($baseUrl)), '/');

            return $path !== '' ? $path : null;
        }

        $folder = trim((string) config('messenger.media.folder', 'images/messenger/chats'), '/');
        $private = trim((string) config('messenger.media.private_folder', 'private/messenger'), '/');
        foreach ([$folder, $private] as $prefix) {
            $pathPos = strpos($normalized, '/'.$prefix.'/');
            if ($pathPos !== false) {
                return ltrim(substr($normalized, $pathPos), '/');
            }
        }

        return null;
    }
}
