<?php

use App\Http\Controllers\Api\Messenger\ContactController;
use App\Http\Controllers\Api\Messenger\GroupChannelController;
use App\Http\Controllers\Api\Messenger\MessengerController;
use Illuminate\Support\Facades\Route;

Route::prefix('messenger')->as('api.messenger.')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {

        // Conversations
        Route::get('/conversations', [MessengerController::class, 'conversations'])->name('conversations.index');
        Route::post('/conversations', [MessengerController::class, 'createConversation'])->name('conversations.create');
        Route::post('/saved', [MessengerController::class, 'savedConversation'])->name('saved');
        Route::get('/conversations/{conversation}', [MessengerController::class, 'showConversation'])->name('conversations.show');
        Route::delete('/conversations/{conversation}', [MessengerController::class, 'deleteConversation'])->middleware('throttle:messenger.destructive')->name('conversations.delete');
        Route::post('/conversations/{conversation}/clear', [MessengerController::class, 'clearConversation'])->middleware('throttle:messenger.destructive')->name('conversations.clear');
        Route::post('/conversations/{conversation}/mute', [MessengerController::class, 'muteConversation'])->name('conversations.mute');

        // Groups & Channels
        Route::post('/groups', [GroupChannelController::class, 'createGroup'])->middleware('throttle:messenger.destructive')->name('groups.create');
        Route::post('/channels', [GroupChannelController::class, 'createChannel'])->middleware('throttle:messenger.destructive')->name('channels.create');
        Route::get('/communities/search', [GroupChannelController::class, 'search'])->middleware('throttle:messenger.search')->name('communities.search');
        Route::get('/communities/username-check', [GroupChannelController::class, 'checkUsername'])->middleware('throttle:messenger.search')->name('communities.username-check');
        Route::post('/join/username', [GroupChannelController::class, 'joinByUsername'])->name('join.username');
        Route::post('/join/invite', [GroupChannelController::class, 'joinByInvite'])->name('join.invite');

        Route::put('/conversations/{conversation}/info', [GroupChannelController::class, 'update'])->name('conversations.info');
        Route::post('/conversations/{conversation}/image', [GroupChannelController::class, 'uploadImage'])->name('conversations.image');
        Route::get('/conversations/{conversation}/members', [GroupChannelController::class, 'members'])->name('conversations.members');
        Route::get('/conversations/{conversation}/members/{userId}', [GroupChannelController::class, 'memberProfile'])->name('conversations.members.show');
        Route::put('/conversations/{conversation}/members/{userId}', [GroupChannelController::class, 'updateMember'])->name('conversations.members.update');
        Route::post('/conversations/{conversation}/transfer', [GroupChannelController::class, 'transferOwnership'])->name('conversations.transfer');
        Route::post('/conversations/{conversation}/leave', [GroupChannelController::class, 'leave'])->name('conversations.leave');
        Route::post('/conversations/{conversation}/members/{userId}/kick', [GroupChannelController::class, 'kick'])->name('conversations.members.kick');
        Route::post('/conversations/{conversation}/members', [GroupChannelController::class, 'addMembers'])->name('conversations.members.add');
        Route::post('/conversations/{conversation}/join', [GroupChannelController::class, 'join'])->name('conversations.join');

        Route::get('/conversations/{conversation}/join-requests', [GroupChannelController::class, 'joinRequests'])->name('conversations.join-requests');
        Route::post('/conversations/{conversation}/join-requests/{requestId}/approve', [GroupChannelController::class, 'approveJoinRequest'])->name('conversations.join-requests.approve');
        Route::post('/conversations/{conversation}/join-requests/{requestId}/reject', [GroupChannelController::class, 'rejectJoinRequest'])->name('conversations.join-requests.reject');

        Route::get('/conversations/{conversation}/invites', [GroupChannelController::class, 'invites'])->name('conversations.invites');
        Route::post('/conversations/{conversation}/invites', [GroupChannelController::class, 'createInvite'])->name('conversations.invites.create');
        Route::delete('/conversations/{conversation}/invites/{inviteId}', [GroupChannelController::class, 'revokeInvite'])->name('conversations.invites.revoke');

        Route::get('/conversations/{conversation}/bans', [GroupChannelController::class, 'bans'])->name('conversations.bans');
        Route::post('/conversations/{conversation}/members/{userId}/ban', [GroupChannelController::class, 'ban'])->name('conversations.members.ban');
        Route::post('/conversations/{conversation}/members/{userId}/unban', [GroupChannelController::class, 'unban'])->name('conversations.members.unban');
        Route::post('/conversations/{conversation}/members/{userId}/mute', [GroupChannelController::class, 'mute'])->name('conversations.members.mute');
        Route::post('/conversations/{conversation}/members/{userId}/unmute', [GroupChannelController::class, 'unmute'])->name('conversations.members.unmute');

        Route::get('/conversations/{conversation}/search', [GroupChannelController::class, 'searchMessages'])->middleware('throttle:messenger.search')->name('conversations.search');
        Route::get('/conversations/{conversation}/permissions', [GroupChannelController::class, 'permissions'])->name('conversations.permissions');
        Route::put('/conversations/{conversation}/permissions', [GroupChannelController::class, 'setPermission'])->name('conversations.permissions.set');
        Route::get('/conversations/{conversation}/audit-logs', [GroupChannelController::class, 'auditLogs'])->name('conversations.audit-logs');

        Route::post('/messages/{message}/react', [GroupChannelController::class, 'react'])->name('messages.react');
        Route::post('/messages/{message}/view', [GroupChannelController::class, 'view'])->name('messages.view');

        // Messages
        Route::get('/conversations/{conversation}/messages', [MessengerController::class, 'messages'])->name('conversations.messages');
        Route::post('/conversations/{conversation}/messages', [MessengerController::class, 'sendMessage'])->middleware('throttle:messenger.send')->name('conversations.messages.send');
        Route::post('/conversations/{conversation}/forward', [MessengerController::class, 'forwardMessages'])->middleware('throttle:messenger.send')->name('conversations.messages.forward');
        Route::post('/messages/bulk-delete', [MessengerController::class, 'bulkDelete'])->middleware('throttle:messenger.destructive')->name('messages.bulk-delete');
        Route::put('/messages/{message}', [MessengerController::class, 'editMessage'])->middleware('throttle:messenger.destructive')->name('messages.edit');
        Route::delete('/messages/{message}', [MessengerController::class, 'deleteMessage'])->middleware('throttle:messenger.destructive')->name('messages.delete');
        // Pinned messages
        Route::get('/conversations/{conversation}/pins', [MessengerController::class, 'pins'])->name('conversations.pins');
        Route::post('/conversations/{conversation}/unpin-all', [MessengerController::class, 'unpinAll'])->middleware('throttle:messenger.destructive')->name('conversations.unpin-all');
        Route::post('/messages/{message}/pin', [MessengerController::class, 'pinMessage'])->middleware('throttle:messenger.destructive')->name('messages.pin');
        Route::delete('/messages/{message}/pin', [MessengerController::class, 'unpinMessage'])->middleware('throttle:messenger.destructive')->name('messages.unpin');

        Route::post('/conversations/{conversation}/typing', [MessengerController::class, 'typing'])->middleware('throttle:messenger.typing')->name('conversations.typing');
        Route::post('/conversations/{conversation}/read', [MessengerController::class, 'markRead'])->name('conversations.read');
        Route::post('/conversations/{conversation}/delivered', [MessengerController::class, 'markDelivered'])->name('conversations.delivered');

        // Presence (heartbeat / explicit offline)
        Route::post('/presence/ping', [MessengerController::class, 'presencePing'])->middleware('throttle:messenger.presence')->name('presence.ping');
        Route::post('/presence/offline', [MessengerController::class, 'presenceOffline'])->middleware('throttle:messenger.presence')->name('presence.offline');

        // Contacts
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('/contacts/blocked', [ContactController::class, 'blocked'])->name('contacts.blocked');
        Route::post('/contacts', [ContactController::class, 'store'])->name('contacts.store');
        Route::post('/contacts/lookup', [ContactController::class, 'lookup'])->middleware('throttle:messenger.search')->name('contacts.lookup');
        Route::post('/contacts/invite', [ContactController::class, 'invite'])->middleware('throttle:messenger.invite')->name('contacts.invite');
        Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        Route::get('/users/search', [ContactController::class, 'search'])->middleware('throttle:messenger.search')->name('users.search');
        Route::post('/users/{userId}/block', [ContactController::class, 'block'])->name('users.block');
        Route::post('/users/{userId}/unblock', [ContactController::class, 'unblock'])->name('users.unblock');

        // Settings & profiles
        Route::get('/settings', [MessengerController::class, 'getSettings'])->name('settings.show');
        Route::put('/settings', [MessengerController::class, 'updateSettings'])->name('settings.update');
        Route::get('/me', [MessengerController::class, 'myProfile'])->name('me.show');
        Route::put('/me', [MessengerController::class, 'updateMyProfile'])->name('me.update');
        Route::get('/me/username-check', [MessengerController::class, 'checkUsername'])->middleware('throttle:messenger.search')->name('me.username-check');
        Route::get('/users/{userId}/profile', [MessengerController::class, 'userProfile'])->name('users.profile');

        // Utility
        Route::get('/unread-count', [MessengerController::class, 'unreadCount'])->name('unread-count');
        Route::get('/sync', [MessengerController::class, 'sync'])->middleware('throttle:messenger.sync')->name('sync');
    });
});
