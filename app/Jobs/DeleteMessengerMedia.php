<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\MessengerMedia;
use App\Models\MessengerSticker;
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
 *
 * When $mediaId is provided, re-checks live message/sticker references before
 * deleting blobs so shared forwards cannot lose storage under a race.
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
        public readonly ?int $mediaId = null,
    ) {}

    public function handle(): void
    {
        if ($this->mediaId) {
            $stillReferenced = Message::query()->where('media_id', $this->mediaId)->exists()
                || MessengerSticker::query()->where('media_id', $this->mediaId)->exists();
            if ($stillReferenced) {
                // Another message re-acquired the asset (or the delete rolled back).
                $media = MessengerMedia::withTrashed()->find($this->mediaId);
                if ($media?->trashed()) {
                    $media->restore();
                }
                if ($media) {
                    $live = $media->liveReferenceCount();
                    if ((int) $media->ref_count !== $live) {
                        $media->forceFill(['ref_count' => $live])->saveQuietly();
                    }
                }

                return;
            }
        }

        if (! is_array($this->meta) || $this->meta === []) {
            return;
        }

        $diskName = $this->meta['disk'] ?? config('messenger.media.disk', 'static');
        $disk = Storage::disk($diskName);
        $paths = [];

        foreach (['path', 'thumb_path', 'cover_path', 'hls_path'] as $key) {
            $val = $this->meta[$key] ?? null;
            if (is_string($val) && $val !== '') {
                $paths[] = ltrim(str_replace('\\', '/', $val), '/');
                // HLS master lives in a folder — delete sibling segments when GC'ing.
                if ($key === 'hls_path') {
                    $hlsDir = dirname(ltrim(str_replace('\\', '/', $val), '/'));
                    if ($hlsDir && $hlsDir !== '.' && $this->isAllowedMediaPath($hlsDir.'/x')) {
                        try {
                            $disk->deleteDirectory($hlsDir);
                        } catch (\Throwable $e) {
                            Log::warning('messenger.hls_dir_delete_failed', [
                                'path' => $hlsDir,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
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
            // Final shared-path guard for legacy meta-only rows.
            if ($this->pathStillReferenced($path)) {
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

    protected function pathStillReferenced(string $path): bool
    {
        $messageRefs = Message::query()
            ->where(function ($q) use ($path) {
                $q->where('meta->path', $path)
                    ->orWhereHas('media', fn ($m) => $m->where('path', $path));
            })
            ->exists();
        if ($messageRefs) {
            return true;
        }

        $stickerRefs = MessengerSticker::query()
            ->whereHas('media', function ($m) use ($path) {
                $m->where('path', $path);
                if ($this->mediaId) {
                    $m->where('id', '!=', $this->mediaId);
                }
            })
            ->exists();
        if ($stickerRefs) {
            return true;
        }

        // Another live media row sharing the same blob (exclude the row we are GC'ing).
        $otherMedia = MessengerMedia::query()->where('path', $path);
        if ($this->mediaId) {
            $otherMedia->where('id', '!=', $this->mediaId);
        }

        return $otherMedia->exists();
    }

    protected function isAllowedMediaPath(string $path): bool
    {
        $folder = trim((string) config('messenger.media.folder', 'images/messenger/chats'), '/');
        $private = trim((string) config('messenger.media.private_folder', 'private/messenger'), '/');
        foreach ([$folder, $private, 'images/messenger/wallpapers', 'images/messenger/stickers'] as $prefix) {
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
