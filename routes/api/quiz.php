<?php

use App\Http\Controllers\Api\Quiz\StudentQuizController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('quiz')->as('api.quiz.')->group(function () {
    Route::get('/quizzes/{quiz:uuid}', [StudentQuizController::class, 'show'])->name('show');
    Route::post('/quizzes/{quiz:uuid}/start', [StudentQuizController::class, 'start'])->name('start');
    Route::get('/history', [StudentQuizController::class, 'history'])->name('history');

    Route::post('/attempts/{attempt:uuid}/save', [StudentQuizController::class, 'save'])->name('attempt.save');
    Route::post('/attempts/{attempt:uuid}/submit', [StudentQuizController::class, 'submit'])->name('attempt.submit');
    Route::post('/attempts/{attempt:uuid}/resume', [StudentQuizController::class, 'resume'])->name('attempt.resume');
    Route::get('/attempts/{attempt:uuid}/result', [StudentQuizController::class, 'result'])->name('attempt.result');
});
