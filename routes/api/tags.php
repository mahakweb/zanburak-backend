<?php

use Illuminate\Support\Facades\Route;

Route::prefix('tags')->as('api.tags.')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\TagController::class, 'index'])->name('index');
    Route::post('/{slug}', [\App\Http\Controllers\Api\TagController::class, 'show'])->name('show');
    Route::post('/{slug}/content', [\App\Http\Controllers\Api\TagController::class, 'content'])->name('content');
});
