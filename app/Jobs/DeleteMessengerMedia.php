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
 * Best-effort CDN cleanup after a messenger media message is deleted for everyone.
 * Runs in the background so the delete API stays fast.
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

        $diskName = config('messenger.media.disk', 'static');
        $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url", ''), '/');
        if ($baseUrl === '') {
            return;
        }

        $urls = [];
        foreach (['url', 'thumb_url', 'cover_url'] as $key) {
            $val = $this->meta[$key] ?? null;
            if (is_string($val) && $val !== '' && ! str_starts_with($val, 'blob:')) {
                $urls[] = $val;
            }
        }

        $disk = Storage::disk($diskName);
        foreach (array_unique($urls) as $url) {
            $path = $this->urlToDiskPath($url, $baseUrl);
            if ($path === null) {
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

    protected function urlToDiskPath(string $url, string $baseUrl): ?string
    {
        $normalized = strtok($url, '?') ?: $url;
        if (! Str::startsWith($normalized, $baseUrl.'/') && $normalized !== $baseUrl) {
            // Also accept protocol-relative / host-agnostic paths under the media folder.
            $folder = trim((string) config('messenger.media.folder', 'images/messenger/chats'), '/');
            $pathPos = strpos($normalized, '/'.$folder.'/');
            if ($pathPos === false) {
                return null;
            }

            return ltrim(substr($normalized, $pathPos), '/');
        }

        $path = ltrim(substr($normalized, strlen($baseUrl)), '/');

        return $path !== '' ? $path : null;
    }
}
