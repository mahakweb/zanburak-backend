<?php

namespace App\Support\Certificate;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class CertificateAssetHelper
{
    public const DISK = 'static';

    private const LEGACY_DISK = 'public';

    private const PATH_PREFIX = 'certificates/';

    public static function disk()
    {
        return Storage::disk(self::DISK);
    }

    public static function baseUrl(): string
    {
        return rtrim((string) config('filesystems.disks.static.url', 'https://static.zanburak.ir'), '/');
    }

    public static function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return self::normalizeExistingUrl($path);
        }

        $relativePath = ltrim(str_replace('\\', '/', $path), '/');

        if (self::disk()->exists($relativePath)) {
            return self::baseUrl().'/'.$relativePath;
        }

        if (Storage::disk(self::LEGACY_DISK)->exists($relativePath)) {
            return url('/api/certificate/asset?path='.rawurlencode($relativePath));
        }

        return self::baseUrl().'/'.$relativePath;
    }

    public static function normalizeExistingUrl(string $url): string
    {
        if (preg_match('#/storage/(.+?)(?:\?.*)?$#', $url, $matches)) {
            return self::publicUrl(urldecode($matches[1]));
        }

        if (preg_match('#/certificate/asset\?path=([^&]+)#', $url, $matches)) {
            return self::publicUrl(urldecode($matches[1]));
        }

        return $url;
    }

    public static function relativePath(?string $pathOrUrl): ?string
    {
        if (! $pathOrUrl) {
            return null;
        }

        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            $baseUrl = self::baseUrl();
            if (str_starts_with($pathOrUrl, $baseUrl)) {
                return ltrim(substr($pathOrUrl, strlen($baseUrl)), '/');
            }

            $parsed = parse_url($pathOrUrl);
            $path = ltrim($parsed['path'] ?? '', '/');
            if (str_starts_with($path, self::PATH_PREFIX)) {
                return $path;
            }

            return null;
        }

        return ltrim(str_replace('\\', '/', $pathOrUrl), '/');
    }

    public static function validateRelativePath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (! str_starts_with($path, self::PATH_PREFIX)) {
            abort(403, 'Invalid asset path');
        }

        if (str_contains($path, '..')) {
            abort(403, 'Invalid asset path');
        }

        return $path;
    }

    public static function exists(?string $pathOrUrl): bool
    {
        $path = self::relativePath($pathOrUrl);
        if (! $path) {
            return false;
        }

        if (self::disk()->exists($path)) {
            return true;
        }

        return Storage::disk(self::LEGACY_DISK)->exists($path);
    }

    public static function delete(?string $pathOrUrl): void
    {
        $path = self::relativePath($pathOrUrl);
        if (! $path) {
            return;
        }

        if (self::disk()->exists($path)) {
            self::disk()->delete($path);
        }

        if (Storage::disk(self::LEGACY_DISK)->exists($path)) {
            Storage::disk(self::LEGACY_DISK)->delete($path);
        }
    }

    public static function uploadFile(UploadedFile $file, string $directory): string
    {
        $directory = trim($directory, '/');
        if (! str_starts_with($directory, self::PATH_PREFIX) || str_contains($directory, '..')) {
            abort(403, 'Invalid asset path');
        }

        $path = self::disk()->putFile($directory, $file);
        if (! $path || ! is_string($path)) {
            abort(500, 'Failed to upload certificate asset to static storage');
        }

        return $path;
    }

    public static function put(string $relativePath, string $contents): string
    {
        $relativePath = self::validateRelativePath($relativePath);
        self::disk()->put($relativePath, $contents);

        return $relativePath;
    }

    /** Sync bundled font from local public storage to static host when missing. */
    public static function syncBundledToStatic(string $relativePath): bool
    {
        $relativePath = self::validateRelativePath($relativePath);

        if (self::disk()->exists($relativePath)) {
            return true;
        }

        if (! Storage::disk(self::LEGACY_DISK)->exists($relativePath)) {
            return false;
        }

        $contents = Storage::disk(self::LEGACY_DISK)->get($relativePath);
        self::disk()->put($relativePath, $contents);

        return self::disk()->exists($relativePath);
    }

    /** Legacy local path for API asset proxy (public disk only). */
    public static function absolutePath(string $path): string
    {
        $path = self::validateRelativePath($path);

        if (! Storage::disk(self::LEGACY_DISK)->exists($path)) {
            abort(404);
        }

        return Storage::disk(self::LEGACY_DISK)->path($path);
    }
}
