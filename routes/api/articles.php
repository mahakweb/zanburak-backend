<?php

use Illuminate\Support\Facades\Route;

Route::prefix('articles')->as('api.articles.')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\ArticleController::class, 'index'])->name('index');
    Route::post('/featured', [\App\Http\Controllers\Api\ArticleController::class, 'featured'])->name('featured');
    Route::post('/sections', [\App\Http\Controllers\Api\ArticleController::class, 'sections'])->name('sections');
    Route::post('/layouts/getInitData', [\App\Http\Controllers\Api\ArticleController::class, 'getInitData'])->name('layouts.init');
    Route::post('/user/{username}', [\App\Http\Controllers\Api\ArticleController::class, 'userArticles'])->name('user');
});
