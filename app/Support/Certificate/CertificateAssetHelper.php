<?php

namespace App\Support\Certificate;

use Illuminate\Support\Facades\Storage;

final class CertificateAssetHelper
{
    public static function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return self::normalizeExistingUrl($path);
        }

        return url('/api/certificate/asset?path='.rawurlencode($path));
    }

    public static function normalizeExistingUrl(string $url): string
    {
        if (preg_match('#/storage/(.+?)(?:\?.*)?$#', $url, $matches)) {
            return self::publicUrl(urldecode($matches[1]));
        }

        return $url;
    }

    public static function absolutePath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (! str_starts_with($path, 'certificates/')) {
            abort(403, 'Invalid asset path');
        }

        if (str_contains($path, '..')) {
            abort(403, 'Invalid asset path');
        }

        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->path($path);
    }
}
