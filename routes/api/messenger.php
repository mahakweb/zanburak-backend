<?php

use App\Http\Controllers\Api\Messenger\ContactController;
use App\Http\Controllers\Api\Messenger\MessengerController;
use Illuminate\Support\Facades\Route;

Route::prefix('messenger')->as('api.messenger.')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {

        // Conversations
        Route::get('/conversations', [MessengerController::class, 'conversations'])->name('conversations.index');
        Route::post('/conversations', [MessengerController::class, 'createConversation'])->name('conversations.create');
        Route::get('/conversations/{conversation}', [MessengerController::class, 'showConversation'])->name('conversations.show');
        Route::delete('/conversations/{conversation}', [MessengerController::class, 'deleteConversation'])->name('conversations.delete');
        Route::post('/conversations/{conversation}/clear', [MessengerController::class, 'clearConversation'])->name('conversations.clear');
        Route::post('/conversations/{conversation}/mute', [MessengerController::class, 'muteConversation'])->name('conversations.mute');

        // Messages
        Route::get('/conversations/{conversation}/messages', [MessengerController::class, 'messages'])->name('conversations.messages');
        Route::post('/conversations/{conversation}/messages', [MessengerController::class, 'sendMessage'])->name('conversations.messages.send');
        Route::post('/conversations/{conversation}/forward', [MessengerController::class, 'forwardMessages'])->name('conversations.messages.forward');
        Route::post('/messages/bulk-delete', [MessengerController::class, 'bulkDelete'])->name('messages.bulk-delete');
        Route::put('/messages/{message}', [MessengerController::class, 'editMessage'])->name('messages.edit');
        Route::delete('/messages/{message}', [MessengerController::class, 'deleteMessage'])->name('messages.delete');
        Route::post('/conversations/{conversation}/typing', [MessengerController::class, 'typing'])->name('conversations.typing');
        Route::post('/conversations/{conversation}/read', [MessengerController::class, 'markRead'])->name('conversations.read');

        // Presence (heartbeat / explicit offline)
        Route::post('/presence/ping', [MessengerController::class, 'presencePing'])->name('presence.ping');
        Route::post('/presence/offline', [MessengerController::class, 'presenceOffline'])->name('presence.offline');

        // Contacts
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('/contacts/blocked', [ContactController::class, 'blocked'])->name('contacts.blocked');
        Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
        Route::post('/contacts/lookup', [ContactController::class, 'lookup'])->name('contacts.lookup');
        Route::post('/contacts/invite', [ContactController::class, 'invite'])->name('contacts.invite');
        Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        Route::get('/users/search', [ContactController::class, 'search'])->name('users.search');
        Route::post('/users/{userId}/block', [ContactController::class, 'block'])->name('users.block');
        Route::post('/users/{userId}/unblock', [ContactController::class, 'unblock'])->name('users.unblock');

        // Settings & profiles
        Route::get('/settings', [MessengerController::class, 'getSettings'])->name('settings.show');
        Route::put('/settings', [MessengerController::class, 'updateSettings'])->name('settings.update');
        Route::get('/me', [MessengerController::class, 'myProfile'])->name('me.show');
        Route::put('/me', [MessengerController::class, 'updateMyProfile'])->name('me.update');
        Route::get('/users/{userId}/profile', [MessengerController::class, 'userProfile'])->name('users.profile');

        // Utility
        Route::get('/unread-count', [MessengerController::class, 'unreadCount'])->name('unread-count');
        Route::get('/sync', [MessengerController::class, 'sync'])->name('sync');
    });
});
