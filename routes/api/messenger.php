<?php

use Illuminate\Support\Facades\Route;

Route::prefix('messenger')->as('api.messenger.')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/conversations', [\App\Http\Controllers\Api\Messenger\MessengerController::class, 'conversations'])->name('conversations.index');
        Route::post('/conversations', [\App\Http\Controllers\Api\Messenger\MessengerController::class, 'createConversation'])->name('conversations.create');
        Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\Messenger\MessengerController::class, 'messages'])->name('conversations.messages');
        Route::post('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\Messenger\MessengerController::class, 'sendMessage'])->name('conversations.messages.send');
        Route::post('/conversations/{conversation}/typing', [\App\Http\Controllers\Api\Messenger\MessengerController::class, 'typing'])->name('conversations.typing');
        Route::post('/conversations/{conversation}/read', [\App\Http\Controllers\Api\Messenger\MessengerController::class, 'markRead'])->name('conversations.read');
    });
});
