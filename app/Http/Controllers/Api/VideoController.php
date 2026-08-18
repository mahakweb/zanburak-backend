<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Episode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class VideoController extends Controller
{

    public function episodeVideo(Episode $episode){
        $stream = $episode->videos()->where('type', 'stream')->first();
        if (!$stream || empty($stream->path)) {
            return response()->json(['message' => 'Stream not ready'], 404);
        }

        $path = parse_url($stream->path, PHP_URL_PATH) ?: $stream->path;
        $disk = $stream->disk ?: 'static';

        return redirect(URL::temporarySignedRoute(
            'api.video-playlist',
            now()->addMinutes(60),
            ['path' => ltrim($path, '/'), 'disk' => $disk]
        ));
    }

    public function courseVideo(Course $course){
        // Prefer trailer record from videos table if it's an HLS master playlist
        $trailer = $course->videos()->where('type', 'trailer')->first();

        if ($trailer && str_contains($trailer->path, '.m3u8')) {
            $path = parse_url($trailer->path, PHP_URL_PATH);
            $disk = $trailer->disk;
            return redirect(URL::temporarySignedRoute('api.video-playlist', now()->addMinutes(20), ['path' => $path, 'disk' => $disk]));
        }

        // Backward compatibility: fall back to course->trailer value if it already points to HLS
        $path = parse_url($course->trailer, PHP_URL_PATH);
        $disk = $course->disk;
        return redirect(URL::temporarySignedRoute('api.video-playlist', now()->addMinutes(20), ['path' => $path, 'disk' => $disk]));
    }

    public function videoKey(){
        $disk = request()->disk ?: 'static';
        $path = (string) request()->path;
        $storage = Storage::disk($disk);

        try {
            $content = $storage->get($path);
        } catch (\Throwable $e) {
            $content = null;
        }

        // Encoder may store keys next to the playlist OR under keys/.
        if ($content === null) {
            if (str_contains($path, '/keys/')) {
                $alt = str_replace('/keys/', '/', $path);
            } else {
                $alt = preg_replace('#/([^/]+)$#', '/keys/$1', $path) ?: $path;
            }
            try {
                $content = $storage->get($alt);
                $path = $alt;
            } catch (\Throwable $e) {
                abort(404);
            }
        }

        return response($content, 200, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function videoM3u8(){
        $disk = request()->disk ?: 'static';
        $path = (string) request()->path;
        $content = Storage::disk($disk)->get($path);
        $isSegment = str_ends_with(strtolower($path), '.ts');

        return response($content, 200, [
            'Content-Type' => $isSegment ? 'video/mp2t' : 'application/vnd.apple.mpegurl',
            // Segments are immutable; playlists can be cached briefly.
            'Cache-Control' => $isSegment ? 'private, max-age=86400, immutable' : 'private, max-age=30',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }


    public function videoPlaylist(){
        $disk = request()->disk ?: 'static';
        $playlistPath = ltrim((string) request()->path, '/');
        $baseDir = rtrim(str_replace('\\', '/', dirname($playlistPath)), '/');
        if ($baseDir === '.' || $baseDir === '') {
            $baseDir = '';
        } else {
            $baseDir .= '/';
        }

        // Avoid FTP exists() probes in resolvers — they add seconds of latency per key/segment.
        return FFMpeg::dynamicHLSPlaylist()
        ->fromDisk($disk)
        ->open($playlistPath)
        ->setKeyUrlResolver(function ($key) use ($disk, $baseDir) {
            $key = basename(ltrim(str_replace('\\', '/', (string) $key), '/'));
            $path = $baseDir . 'keys/' . $key;

            return URL::temporarySignedRoute('api.video-key', now()->addMinutes(60), [
                'path' => $path,
                'disk' => $disk,
            ]);
        })
        ->setMediaUrlResolver(function ($mediaFilename) use ($disk, $baseDir) {
            return URL::temporarySignedRoute('api.video-m3u8', now()->addMinutes(60), [
                'path' => $baseDir . ltrim((string) $mediaFilename, '/'),
                'disk' => $disk,
            ]);
        })
        ->setPlaylistUrlResolver(function ($playlistFilename) use ($disk, $baseDir) {
            return URL::temporarySignedRoute('api.video-playlist', now()->addMinutes(60), [
                'path' => $baseDir . ltrim((string) $playlistFilename, '/'),
                'disk' => $disk,
            ]);
        });
    }
}
