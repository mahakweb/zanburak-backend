<?php

use Illuminate\Support\Facades\Route;

// Profile routes (public read endpoints)
Route::prefix('/@{username}')->as('api.profile.')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'index'])->name('index');
    Route::post('/about', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'about'])->name('about');
    Route::post('/discuss', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'discuss'])->name('discuss');
    Route::post('/discuss/answers', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'answers'])->name('discuss.answers');
});


