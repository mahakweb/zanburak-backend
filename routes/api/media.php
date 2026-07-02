<?php

use Illuminate\Support\Facades\Route;

// Video playlists and keys (signed where needed)
Route::get('episode/{episode}/playlist', [\App\Http\Controllers\Api\VideoController::class, 'episodeVideo'])->name('api.episode-video');
Route::get('course/{course}/playlist', [\App\Http\Controllers\Api\VideoController::class, 'courseVideo'])->name('api.course-video');
Route::get('/hls/key', [\App\Http\Controllers\Api\VideoController::class, 'videoKey'])->name('api.video-key')->middleware(['signed', 'video.player']);
Route::get('/hls/segment', [\App\Http\Controllers\Api\VideoController::class, 'videoM3u8'])->name('api.video-m3u8')->middleware(['signed', 'video.player']);
Route::get('/hls/manifest', [\App\Http\Controllers\Api\VideoController::class, 'videoPlaylist'])->name('api.video-playlist')->middleware(['signed', 'video.player']);

// Video views & downloads
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/video-views/{video}/getWatched', [\App\Http\Controllers\Api\VideoViewsController::class, 'getWatched'])->name('api.video-views.get-watched');
    Route::post('/video-views/{video}/setWatched', [\App\Http\Controllers\Api\VideoViewsController::class, 'setWatched'])->name('api.video-views.set-watched');
    Route::post('/episode/{episode}/checkDownload', [\App\Http\Controllers\Api\Course\EpisodeController::class, 'episodeDownloadCheck'])->name('api.episode.check-download');
});
Route::middleware('signed')->get('/episode/{episode}/download', [\App\Http\Controllers\Api\Course\EpisodeController::class, 'episodeDownload'])->name('api.episode-download');


