<?php

use Illuminate\Support\Facades\Route;

Route::prefix('article')->as('api.article.')->group(function () {
    Route::post('/{articleSlug}', [\App\Http\Controllers\Api\ArticleController::class, 'show'])->name('show');
    Route::post('/{articleSlug}/related', [\App\Http\Controllers\Api\ArticleController::class, 'related'])->name('related');
    Route::post('/{articleSlug}/prev-next', [\App\Http\Controllers\Api\ArticleController::class, 'prevNext'])->name('prev-next');
});
