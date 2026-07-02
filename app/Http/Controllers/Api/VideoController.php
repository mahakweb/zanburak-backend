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
        // $episode = Episode::findOrFail(request()->episode);
        $path = parse_url($episode->videos->where('type', 'stream')->pluck('path')[0], PHP_URL_PATH);
        $disk = $episode->videos->where('type', 'stream')->pluck('disk')[0];
        return redirect(URL::temporarySignedRoute('api.video-playlist', now()->addMinutes(20), ['path' => $path, 'disk' => $disk]));
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
        $content = Storage::disk(request()->disk)->get(request()->path);

        return response($content, 200, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function videoM3u8(){
        $content = Storage::disk(request()->disk)->get(request()->path);

        return response($content, 200, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }


    public function videoPlaylist(){
        // var_dump(request()->path);
        // var_dump(request()->disk);
        $disk = 'static';
        if(request()->disk)
            $disk = request()->disk;
        return FFMpeg::dynamicHLSPlaylist()
        ->fromDisk($disk)
        ->open(request()->path)
        ->setKeyUrlResolver(function ($key) {
            return (URL::temporarySignedRoute('api.video-key', now()->addMinutes(20), ['path' => str_replace(substr(request()->path, strrpos(request()->path, "/")+1),"", request()->path)."keys/".$key, 'disk' => request()->disk]));
        })
        ->setMediaUrlResolver(function ($mediaFilename) {
            return (URL::temporarySignedRoute('api.video-m3u8', now()->addMinutes(20), ['path' => str_replace(substr(request()->path, strrpos(request()->path, "/")+1),"", request()->path).$mediaFilename, 'disk' => request()->disk]));
        })
        ->setPlaylistUrlResolver(function ($playlistFilename) {
            return (URL::temporarySignedRoute('api.video-playlist', now()->addMinutes(20),['path' => str_replace(substr(request()->path, strrpos(request()->path, "/")+1),"", request()->path).$playlistFilename, 'disk' => request()->disk]));
        });
    }
}
