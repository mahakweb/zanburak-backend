<?php

use Illuminate\Support\Facades\Route;

// Discuss routes
Route::prefix('discuss')->as('api.discuss.')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'discussions'])->name('index');
    Route::post('/{questionSlug}', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'discuss'])->name('show');
    Route::post('/{questionSlug}/answers', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'answers'])->name('answers');
    Route::post('/layouts/getInitData', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'getInitData'])->name('layouts.init');
    Route::post('/layouts/similarQuestions', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'similarQuestions'])->name('layouts.similar');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{questionSlug}/edit', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'getQuestionForEdit'])->name('edit');
        Route::put('/{questionSlug}/edit', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'updateQuestion'])->name('update');
        Route::put('/{questionSlug}/editAnswer', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'updateAnswer'])->name('answer.update');
        Route::put('/{questionSlug}/setBestAnswer', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'setBestAnswer'])->name('answer.best');
        Route::delete('/{questionSlug}/delete', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'deleteQuestion'])->name('delete');
        Route::delete('/{questionSlug}/deleteAnswer', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'deleteAnswer'])->name('answer.delete');
        Route::post('/{questionSlug}/newAnswer', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'newAnswer'])->name('answer.create');
        Route::post('/layouts/create', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'create'])->name('create');
        Route::post('/layouts/like-dislike', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'likeDislike'])->name('layouts.like-dislike');
        Route::post('/layouts/toggle-pin', [\App\Http\Controllers\Api\Discuss\DiscussController::class, 'togglePin'])->name('layouts.toggle-pin');
    });
});


