<?php

namespace App\Http\Controllers\Api\Admin\Course;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Public-but-signed admin file/playlist viewer (no Bearer header in new tab).
 */
class VideoViewController extends Controller
{
    public function __invoke(Video $video)
    {
        if (!$video->path || !$video->disk) {
            abort(404, 'Video path missing');
        }

        if ($video->type === 'stream' || str_ends_with(strtolower((string) $video->path), '.m3u8')) {
            $path = ltrim((string) (parse_url($video->path, PHP_URL_PATH) ?: $video->path), '/');

            return redirect(URL::temporarySignedRoute(
                'api.video-playlist',
                now()->addMinutes(90),
                ['path' => $path, 'disk' => $video->disk ?: 'media']
            ));
        }

        $diskName = (string) $video->disk;
        if (!config("filesystems.disks.{$diskName}")) {
            abort(404, 'Unknown disk');
        }

        $storage = Storage::disk($diskName);
        $path = ltrim(str_replace('\\', '/', (string) $video->path), '/');

        try {
            if (!$storage->exists($path)) {
                abort(404, 'File not found on disk');
            }
        } catch (\Throwable $e) {
            abort(404, 'File not found on disk');
        }

        $filename = basename($path) ?: 'video.bin';
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            'mov' => 'video/quicktime',
            'm3u8' => 'application/vnd.apple.mpegurl',
            default => 'application/octet-stream',
        };

        return new StreamedResponse(function () use ($storage, $path) {
            $stream = $storage->readStream($path);
            if (!is_resource($stream)) {
                return;
            }
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'private, max-age=120',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
