<?php

use Illuminate\Support\Facades\Route;

// General public endpoints
Route::post('get-plans-list', [\App\Http\Controllers\Api\IndexController::class, 'plansList'])->name('api.plans.list');
Route::post('/search', [\App\Http\Controllers\Api\SearchController::class, 'search'])->name('api.search');

// Editor upload
Route::middleware('auth:sanctum')->post('/editor/uploadImage', [\App\Http\Controllers\Api\IndexController::class, 'editorUploadImage'])->name('api.editor.upload-image');
Route::middleware('auth:sanctum')->post('/editor/uploadFile', [\App\Http\Controllers\Api\IndexController::class, 'editorUploadFile'])->name('api.editor.upload-file');
Route::middleware('auth:sanctum')->post('/editor/uploadVideo', [\App\Http\Controllers\Api\IndexController::class, 'editorUploadVideo'])->name('api.editor.upload-video');

// Request project
Route::middleware('auth:sanctum')->prefix('request-project')->as('api.request-project.')->group(function () {
    Route::post('check', [\App\Http\Controllers\Api\IndexController::class, 'requestProjectCheck'])->name('check');
    Route::post('/', [\App\Http\Controllers\Api\IndexController::class, 'requestProject'])->name('store');
    Route::post('upload-file', [\App\Http\Controllers\Api\IndexController::class, 'dropzoneUpload'])->name('upload-file');
});

// Cooperation
Route::middleware('auth:sanctum')->prefix('cooperation')->as('api.cooperation.')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\IndexController::class, 'cooperation'])->name('store');
    // Reuse dropzone upload endpoint (stores in /project/ path for now)
    Route::post('upload-file', [\App\Http\Controllers\Api\IndexController::class, 'uploadFileCooperation'])->name('upload-file-cooperation');
    Route::post('validate-iban', [\App\Http\Controllers\Api\IndexController::class, 'validateIban'])->name('validate-iban');
});

// Certificate
Route::get('/certificate/fonts', [\App\Http\Controllers\Api\CertificateFontController::class, 'index'])->name('api.certificate.fonts');
Route::get('/certificate/asset', [\App\Http\Controllers\Api\CertificateController::class, 'asset'])->name('api.certificate.asset');
Route::get('/certificate/{uuid}', [\App\Http\Controllers\Api\CertificateController::class, 'index'])->name('api.certificate.show');
Route::get('/certificate/{uuid}/render', [\App\Http\Controllers\Api\CertificateController::class, 'renderData'])->name('api.certificate.render');
Route::get('/certificate/{uuid}/download/qr', [\App\Http\Controllers\Api\CertificateController::class, 'downloadQr'])->name('api.certificate.download.qr');
Route::post('/certificate/verify', [\App\Http\Controllers\Api\CertificateController::class, 'verify'])->name('api.certificate.verify');

// Interactions (auth required)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/toggleBookmark', [\App\Http\Controllers\Api\BookmarkController::class, 'toggleBookmark'])->name('api.bookmark.toggle');
    Route::post('/toggleLike', [\App\Http\Controllers\Api\LikeController::class, 'toggleLike'])->name('api.like.toggle');
    Route::post('/toggleFollow', [\App\Http\Controllers\Api\FollowController::class, 'toggleFollow'])->name('api.follow.toggle');
    Route::post('/sendReport', [\App\Http\Controllers\Api\ReportController::class, 'sendReport'])->name('api.report.send');
    Route::post('/setRate', [\App\Http\Controllers\Api\RatingController::class, 'setRate'])->name('api.rating.set');
});


