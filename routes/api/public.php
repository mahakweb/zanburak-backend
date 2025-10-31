<?php

use Illuminate\Support\Facades\Route;

// Index and paths
Route::get('/index/latestCourses', [\App\Http\Controllers\Api\IndexController::class, 'latestCourses'])->name('api.index.latest-courses');
Route::get('/paths', [\App\Http\Controllers\Api\IndexController::class, 'paths'])->name('api.paths.index');
Route::get('/path/{pathSlug}', [\App\Http\Controllers\Api\IndexController::class, 'getPath'])->name('api.paths.show');

// Comments
Route::get('/comments', [\App\Http\Controllers\Api\CommentController::class, 'index'])->name('api.comments.index');
Route::middleware('auth:sanctum')->post('/comments/store', [\App\Http\Controllers\Api\CommentController::class, 'store'])->name('api.comments.store');

// Courses
Route::get('/courses', [\App\Http\Controllers\Api\Course\CourseController::class, 'courses'])->name('api.courses.index');
Route::get('/courses-page-filter', [\App\Http\Controllers\Api\Course\CourseController::class, 'filters'])->name('api.courses.filters');
Route::get('/course/{courseSlug}', [\App\Http\Controllers\Api\Course\CourseController::class, 'getCourse'])->name('api.courses.show');
Route::get('/course/{courseSlug}/episode/{episodeSlug}', [\App\Http\Controllers\Api\Course\EpisodeController::class, 'getEpisode'])->name('api.episodes.show');


