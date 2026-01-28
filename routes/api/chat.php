<?php

use Illuminate\Support\Facades\Route;

// Chat routes (auth required)
Route::middleware('auth:sanctum')->as('api.chat.')->group(function () {
    Route::get('/messages', [\App\Http\Controllers\Api\Chat\ChatController::class, 'getMessages'])->name('messages');
    Route::get('/messages/{contact}', [\App\Http\Controllers\Api\Chat\ChatController::class, 'getMessagesContact'])->name('messages.contact');
    Route::get('/user/contacts', [\App\Http\Controllers\Api\Chat\ContactController::class, 'index'])->name('contacts');
    Route::get('/chats', [\App\Http\Controllers\Api\Chat\ChatController::class, 'chats'])->name('chats');
    Route::get('/chat/@{username}', [\App\Http\Controllers\Api\Chat\ChatController::class, 'getMessages'])->name('user');
    Route::post('/chat/@{username}/send-message', [\App\Http\Controllers\Api\Chat\ChatController::class, 'sendMessage'])->name('send-message');
    Route::post('/chat/mark-as-read/@{username}', [\App\Http\Controllers\Api\Chat\ChatController::class, 'markAsRead'])->name('mark-as-read');
    Route::post('/chat/delete-message/{message}', [\App\Http\Controllers\Api\Chat\ChatController::class, 'deleteMessage'])->name('delete-message');
    Route::post('/chat/edit-message/{message}', [\App\Http\Controllers\Api\Chat\ChatController::class, 'editMessage'])->name('edit-message');
    Route::post('/chat/@{username}/typing', [\App\Http\Controllers\Api\Chat\ChatController::class, 'typing'])->name('typing');
});


