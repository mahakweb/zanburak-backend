<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Best-effort sibling .webp next to a raster image on a storage disk.
 * Used by main API (poster/cover upload) so SeoImage can prefer WebP.
 */
class ImageWebpService
{
    /**
     * Shared placeholders must never be deleted (many courses/paths point to them).
     */
    public function isSharedPlaceholder(?string $urlOrPath): bool
    {
        if (! $urlOrPath) {
            return false;
        }
        $p = strtolower($urlOrPath);

        return str_contains($p, 'no-image')
            || str_contains($p, 'placeholder')
            || str_contains($p, '/default.')
            || str_contains($p, 'default.png')
            || str_contains($p, 'default.jpg')
            || str_contains($p, 'default.webp');
    }

    /**
     * Flysystem FTP/S3 often throws UnableToRetrieveMetadata instead of false.
     */
    public function diskExists(string $disk, string $path): bool
    {
        try {
            return Storage::disk($disk)->exists($path);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Delete original + sibling .webp when replacing media.
     * Skips shared placeholders; never throws to callers.
     */
    public function safeDeleteStoredMedia(?string $url): void
    {
        if (! $url || $this->isSharedPlaceholder($url)) {
            return;
        }

        try {
            $this->deleteSiblingForUrl($url);
        } catch (\Throwable $e) {
            Log::warning('ImageWebpService sibling delete failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $resolved = $this->resolveUrl($url);
            if (! $resolved) {
                return;
            }
            if ($this->diskExists($resolved['disk'], $resolved['path'])) {
                Storage::disk($resolved['disk'])->delete($resolved['path']);
            }
        } catch (\Throwable $e) {
            Log::warning('ImageWebpService original delete failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{ok: bool, skipped?: bool, reason?: string, webp_path?: string}
     */
    public function ensureSibling(string $disk, string $originalRelativePath, ?string $localSourcePath = null): array
    {
        try {
            if ($this->isSharedPlaceholder($originalRelativePath)) {
                return ['ok' => true, 'skipped' => true, 'reason' => 'shared_placeholder'];
            }

            $ext = strtolower(pathinfo($originalRelativePath, PATHINFO_EXTENSION));
            if (in_array($ext, ['webp', 'svg', 'gif'], true)) {
                return ['ok' => true, 'skipped' => true, 'reason' => 'already_modern'];
            }
            if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                return ['ok' => false, 'skipped' => true, 'reason' => 'unsupported_ext'];
            }
            if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
                return ['ok' => false, 'skipped' => true, 'reason' => 'gd_webp_missing'];
            }

            $webpRel = preg_replace('/\.[^.]+$/', '.webp', $originalRelativePath);
            if (! $webpRel || $webpRel === $originalRelativePath) {
                $webpRel = $originalRelativePath.'.webp';
            }

            if ($this->diskExists($disk, $webpRel)) {
                return ['ok' => true, 'skipped' => true, 'reason' => 'already_exists', 'webp_path' => $webpRel];
            }

            $binary = null;
            if ($localSourcePath && is_file($localSourcePath)) {
                $binary = @file_get_contents($localSourcePath);
            }
            if ($binary === null || $binary === false || $binary === '') {
                if (! $this->diskExists($disk, $originalRelativePath)) {
                    return ['ok' => false, 'reason' => 'source_missing'];
                }
                try {
                    $binary = Storage::disk($disk)->get($originalRelativePath);
                } catch (\Throwable $e) {
                    return ['ok' => false, 'reason' => 'source_read_failed'];
                }
            }
            if (! is_string($binary) || $binary === '') {
                return ['ok' => false, 'reason' => 'empty_source'];
            }

            $img = @imagecreatefromstring($binary);
            if ($img === false) {
                return ['ok' => false, 'reason' => 'decode_failed'];
            }
            if (function_exists('imagepalettetotruecolor')) {
                @imagepalettetotruecolor($img);
            }
            if (function_exists('imagealphablending') && function_exists('imagesavealpha')) {
                @imagealphablending($img, true);
                @imagesavealpha($img, true);
            }

            $tmp = tempnam(sys_get_temp_dir(), 'zanwebp_');
            if ($tmp === false) {
                imagedestroy($img);

                return ['ok' => false, 'reason' => 'tmp_failed'];
            }

            $encoded = @imagewebp($img, $tmp, 82);
            imagedestroy($img);
            if (! $encoded || ! is_file($tmp) || filesize($tmp) < 32) {
                @unlink($tmp);

                return ['ok' => false, 'reason' => 'encode_failed'];
            }

            $dir = ltrim(dirname($webpRel), '/');
            $basename = basename($webpRel);
            try {
                if ($dir && $dir !== '.' && ! $this->diskExists($disk, $dir)) {
                    Storage::disk($disk)->makeDirectory($dir);
                }
            } catch (\Throwable $e) {
                // continue; put may still succeed
            }

            $stream = fopen($tmp, 'rb');
            if (! $stream) {
                @unlink($tmp);

                return ['ok' => false, 'reason' => 'open_tmp_failed'];
            }
            try {
                $putPath = ($dir === '.' || $dir === '') ? $basename : ($dir.'/'.$basename);
                if (Storage::disk($disk)->put($putPath, $stream) === false) {
                    return ['ok' => false, 'reason' => 'put_failed'];
                }
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                @unlink($tmp);
            }

            return ['ok' => true, 'webp_path' => $webpRel];
        } catch (\Throwable $e) {
            Log::warning('ImageWebpService failed', [
                'disk' => $disk,
                'path' => $originalRelativePath,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'reason' => 'exception', 'message' => $e->getMessage()];
        }
    }

    /**
     * @return array{disk: string, path: string}|null
     */
    public function resolveUrl(string $url): ?array
    {
        if (! $url || ! Str::is('http*://*', $url)) {
            return null;
        }

        foreach (config('filesystems.disks') as $disk => $config) {
            if (! isset($config['url'])) {
                continue;
            }
            $baseUrl = rtrim($config['url'], '/');
            if (str_starts_with($url, $baseUrl)) {
                return [
                    'disk' => $disk,
                    'path' => ltrim(str_replace($baseUrl, '', $url), '/'),
                ];
            }
        }

        return null;
    }

    public function deleteSiblingForUrl(string $url): void
    {
        if ($this->isSharedPlaceholder($url)) {
            return;
        }
        $resolved = $this->resolveUrl($url);
        if (! $resolved) {
            return;
        }
        $webpRel = preg_replace('/\.[^.]+$/', '.webp', $resolved['path']);
        if (! $webpRel || $webpRel === $resolved['path']) {
            return;
        }
        try {
            if ($this->diskExists($resolved['disk'], $webpRel)) {
                Storage::disk($resolved['disk'])->delete($webpRel);
            }
        } catch (\Throwable $e) {
            Log::warning('ImageWebpService delete sibling failed', ['error' => $e->getMessage()]);
        }
    }
}
