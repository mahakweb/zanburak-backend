<?php

namespace App\Services\Messenger;

use App\Models\Message;
use App\Models\MessengerMedia;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CDN-ready authenticated media delivery for messenger.
 * Supports Range requests, cache headers, signed temporary URLs, and HLS variants.
 */
class MessengerMediaDeliveryService
{
    public const VARIANT_FILE = 'file';

    public const VARIANT_THUMB = 'thumb';

    public const VARIANT_COVER = 'cover';

    public const VARIANT_HLS = 'hls';

    /**
     * Resolve storage target for a visible message + variant.
     *
     * @return array{disk: string, path: string, mime: string, encrypted: bool, media: ?MessengerMedia, size: ?int, etag: ?string}|JsonResponse
     */
    public function resolveTarget(User $user, Message $message, string $variant = self::VARIANT_FILE): array|JsonResponse
    {
        try {
            $visible = Message::query()
                ->visibleTo($user)
                ->whereKey($message->id)
                ->firstOrFail();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $meta = is_array($visible->meta) ? $visible->meta : [];
        $media = null;
        if ($visible->media_id) {
            $visible->loadMissing('media');
            $media = $visible->media;
            if ($media) {
                // Prefer canonical shared blob when this row is a dedup alias.
                if ($media->canonical_media_id) {
                    $media->loadMissing('canonical');
                    $media = $media->canonical ?: $media;
                }
                $meta = array_merge($media->toMessageMeta(), $meta);
            }
        }

        $pathKey = match ($variant) {
            self::VARIANT_THUMB => 'thumb_path',
            self::VARIANT_COVER => 'cover_path',
            self::VARIANT_HLS => 'hls_path',
            default => 'path',
        };
        $legacyUrlKey = match ($variant) {
            self::VARIANT_THUMB => 'thumb_url',
            self::VARIANT_COVER => 'cover_url',
            default => 'url',
        };

        $diskName = $meta['disk'] ?? config('messenger.media.disk', 'static');
        $path = $meta[$pathKey] ?? null;

        if ($variant === self::VARIANT_HLS) {
            $path = $media?->hls_path ?: ($meta['hls_path'] ?? null);
            if (! is_string($path) || $path === '' || ($media && $media->hls_status !== 'ready')) {
                return response()->json(['message' => 'HLS not ready'], 404);
            }
        }

        if ((! is_string($path) || $path === '') && ! empty($meta[$legacyUrlKey])) {
            $path = $this->pathFromPublicUrl((string) $meta[$legacyUrlKey], $diskName);
        }

        if (! is_string($path) || $path === '') {
            return response()->json(['message' => 'Media missing'], 404);
        }

        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        if (! $this->isAllowedMediaPath($normalized)) {
            return response()->json(['message' => 'Forbidden path'], 403);
        }

        $disk = Storage::disk($diskName);
        try {
            if (! $disk->exists($normalized)) {
                return response()->json(['message' => 'File not found'], 404);
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Storage unavailable'], 503);
        }

        $isEncrypted = ! empty($meta['encrypted']) || ! empty($visible->is_encrypted);
        $mime = $isEncrypted
            ? 'application/octet-stream'
            : match ($variant) {
                self::VARIANT_THUMB, self::VARIANT_COVER => 'image/jpeg',
                self::VARIANT_HLS => str_ends_with(strtolower($normalized), '.m3u8')
                    ? 'application/vnd.apple.mpegurl'
                    : 'application/octet-stream',
                default => $meta['mime'] ?? 'application/octet-stream',
            };

        $size = null;
        try {
            $size = $disk->size($normalized);
        } catch (\Throwable $e) {
            // optional
        }

        $etag = $media?->etag
            ?: ($meta['sha256'] ?? null)
            ?: ($size !== null ? sha1($normalized.'|'.$size) : null);

        return [
            'disk' => $diskName,
            'path' => $normalized,
            'mime' => $mime,
            'encrypted' => $isEncrypted,
            'media' => $media,
            'size' => $size,
            'etag' => $etag ? '"'.$etag.'"' : null,
            'message' => $visible,
        ];
    }

    /**
     * Stream a resolved file with HTTP Range + CDN-friendly cache headers.
     */
    public function stream(Request $request, array $target): StreamedResponse
    {
        $disk = Storage::disk($target['disk']);
        $path = $target['path'];
        $mime = $target['mime'];
        $isEncrypted = (bool) $target['encrypted'];
        $size = $target['size'];
        $etag = $target['etag'];

        // Conditional GET
        if ($etag && $request->headers->get('If-None-Match') === $etag) {
            return response()->stream(fn () => null, 304, array_filter([
                'ETag' => $etag,
                'Cache-Control' => $this->cacheControl($isEncrypted),
                'Accept-Ranges' => $isEncrypted ? 'none' : 'bytes',
            ]));
        }

        $start = 0;
        $end = ($size !== null && $size > 0) ? $size - 1 : null;
        $status = 200;
        $contentLength = $size;

        $rangeHeader = $request->header('Range');
        if (! $isEncrypted && is_string($rangeHeader) && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $m) && $size !== null && $size > 0) {
            $start = $m[1] !== '' ? (int) $m[1] : 0;
            $end = $m[2] !== '' ? (int) $m[2] : ($size - 1);
            $start = max(0, min($start, $size - 1));
            $end = max($start, min($end, $size - 1));
            $contentLength = $end - $start + 1;
            $status = 206;
        }

        $length = $contentLength;
        $byteStart = $start;
        $byteEnd = $end;

        return response()->stream(function () use ($disk, $path, $byteStart, $byteEnd, $length) {
            $stream = $disk->readStream($path);
            if ($stream === false) {
                return;
            }
            if (is_resource($stream) && $byteStart > 0) {
                fseek($stream, $byteStart);
            }
            if ($length !== null && is_resource($stream)) {
                $remaining = (int) $length;
                while ($remaining > 0 && ! feof($stream)) {
                    $chunk = fread($stream, min(65536, $remaining));
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    echo $chunk;
                    $remaining -= strlen($chunk);
                    if (connection_aborted()) {
                        break;
                    }
                }
            } else {
                fpassthru($stream);
            }
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, $status, array_filter([
            'Content-Type' => $mime,
            'Content-Length' => $length !== null ? (string) $length : null,
            'Accept-Ranges' => $isEncrypted ? 'none' : 'bytes',
            'Content-Range' => ($status === 206 && $size !== null && $byteEnd !== null)
                ? "bytes {$byteStart}-{$byteEnd}/{$size}"
                : null,
            'Cache-Control' => $this->cacheControl($isEncrypted),
            'ETag' => $etag,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
            // CDN / edge hints (private content still requires auth or signed URL).
            'Vary' => 'Authorization, Range, Origin',
            'X-Accel-Buffering' => 'no',
            'Access-Control-Allow-Origin' => (($origin = $request->headers->get('Origin')) && $origin !== '')
                ? $origin
                : '*',
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Expose-Headers' => 'Content-Length, Content-Range, Accept-Ranges, ETag',
        ]));
    }

    /**
     * Stream an HLS playlist/segment under the media's HLS folder (signed or auth).
     */
    public function streamHlsAsset(Request $request, array $target, string $relative): StreamedResponse|JsonResponse
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return response()->json(['message' => 'Invalid path'], 400);
        }

        $hlsRoot = dirname($target['path']);
        $full = ltrim($hlsRoot.'/'.$relative, '/');
        if (! $this->isAllowedMediaPath($full)) {
            return response()->json(['message' => 'Forbidden path'], 403);
        }

        $disk = Storage::disk($target['disk']);
        try {
            if (! $disk->exists($full)) {
                return response()->json(['message' => 'File not found'], 404);
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Storage unavailable'], 503);
        }

        $lower = strtolower($full);
        $mime = match (true) {
            str_ends_with($lower, '.m3u8') => 'application/vnd.apple.mpegurl',
            str_ends_with($lower, '.ts') => 'video/mp2t',
            str_ends_with($lower, '.m4s') => 'video/iso.segment',
            str_ends_with($lower, '.mp4') => 'video/mp4',
            str_ends_with($lower, '.key') => 'application/octet-stream',
            default => 'application/octet-stream',
        };

        $size = null;
        try {
            $size = $disk->size($full);
        } catch (\Throwable $e) {
            // optional
        }

        return $this->stream($request, [
            'disk' => $target['disk'],
            'path' => $full,
            'mime' => $mime,
            'encrypted' => false,
            'media' => $target['media'] ?? null,
            'size' => $size,
            'etag' => '"'.sha1($full.'|'.($size ?? 0)).'"',
        ]);
    }

    /**
     * Issue a temporary signed URL for CDN / edge fetch without Bearer on every byte.
     */
    public function issueSignedUrl(User $user, Message $message, string $variant = self::VARIANT_FILE, int $ttlMinutes = 20): array|JsonResponse
    {
        $target = $this->resolveTarget($user, $message, $variant);
        if ($target instanceof JsonResponse) {
            return $target;
        }

        $ttl = max(1, min(60, $ttlMinutes));
        $url = URL::temporarySignedRoute(
            'api.messenger.media.signed',
            now()->addMinutes($ttl),
            [
                'message' => $message->id,
                'v' => $variant,
                'uid' => $user->id,
            ]
        );

        return [
            'url' => $url,
            'expires_in' => $ttl * 60,
            'variant' => $variant,
            'accept_ranges' => ! $target['encrypted'],
            'mime' => $target['mime'],
            'size' => $target['size'],
            'etag' => $target['etag'],
        ];
    }

    public function cacheControl(bool $encrypted): string
    {
        if ($encrypted) {
            return 'private, no-store';
        }

        $maxAge = (int) config('messenger.media.cache_max_age', 3600);

        // Private: browser/CDN may cache for the user session; must revalidate with auth.
        return "private, max-age={$maxAge}, stale-while-revalidate=60";
    }

    public function isAllowedMediaPath(string $path): bool
    {
        $allowedPrefixes = [
            trim((string) config('messenger.media.folder', 'images/messenger/chats'), '/'),
            trim((string) config('messenger.media.private_folder', 'private/messenger'), '/'),
            'images/messenger/stickers',
        ];
        foreach ($allowedPrefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    public function pathFromPublicUrl(string $url, string $diskName): ?string
    {
        $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url", ''), '/');
        $normalized = strtok($url, '?') ?: $url;
        if ($baseUrl !== '' && str_starts_with($normalized, $baseUrl.'/')) {
            return ltrim(substr($normalized, strlen($baseUrl)), '/');
        }

        $folder = trim((string) config('messenger.media.folder', 'images/messenger/chats'), '/');
        $private = trim((string) config('messenger.media.private_folder', 'private/messenger'), '/');
        foreach ([$folder, $private] as $prefix) {
            $pos = strpos($normalized, '/'.$prefix.'/');
            if ($pos !== false) {
                return ltrim(substr($normalized, $pos), '/');
            }
        }

        return null;
    }
}
