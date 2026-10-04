<?php

use App\Http\Controllers\Api\Messenger\ContactController;
use App\Http\Controllers\Api\Messenger\GroupChannelController;
use App\Http\Controllers\Api\Messenger\MessengerController;
use App\Http\Controllers\Api\Messenger\MessengerCryptoController;
use App\Http\Controllers\Api\Messenger\StickerPackController;
use Illuminate\Support\Facades\Route;

Route::prefix('messenger')->as('api.messenger.')->group(function () {

    // Temporary signed media (CDN-ready) — no Sanctum; signature embeds uid + expiry.
    Route::get('/media-signed/{message}', [MessengerCryptoController::class, 'streamSignedMedia'])
        ->middleware('signed')
        ->name('media.signed');

    Route::middleware(['auth:sanctum', 'messenger.access'])->group(function () {

        Route::get('/config', [MessengerController::class, 'systemConfig'])->name('config');

        // End-to-end encryption (device keys + opaque package relay + private media proxy)
        Route::post('/crypto/devices', [MessengerCryptoController::class, 'registerDevice'])->name('crypto.devices.register');
        Route::post('/crypto/prekeys', [MessengerCryptoController::class, 'uploadPrekeys'])->name('crypto.prekeys');
        Route::get('/crypto/devices', [MessengerCryptoController::class, 'myDevices'])->name('crypto.devices.index');
        Route::delete('/crypto/devices/{deviceId}', [MessengerCryptoController::class, 'revokeDevice'])->name('crypto.devices.revoke');
        Route::get('/crypto/conversations/{conversation}/bundles', [MessengerCryptoController::class, 'bundles'])->name('crypto.bundles');
        Route::post('/crypto/conversations/{conversation}/distribute', [MessengerCryptoController::class, 'distribute'])->name('crypto.distribute');
        Route::post('/crypto/conversations/{conversation}/key-request', [MessengerCryptoController::class, 'requestKey'])->name('crypto.key-request');
        Route::get('/crypto/packages', [MessengerCryptoController::class, 'packages'])->name('crypto.packages');
        Route::post('/crypto/packages/ack', [MessengerCryptoController::class, 'ackPackages'])->name('crypto.packages.ack');
        Route::get('/crypto/safety/{userId}', [MessengerCryptoController::class, 'safetyNumber'])->name('crypto.safety');
        Route::get('/crypto/identity', [MessengerCryptoController::class, 'getUserIdentity'])->name('crypto.identity.show');
        Route::post('/crypto/identity', [MessengerCryptoController::class, 'publishUserIdentity'])->name('crypto.identity.publish');
        Route::put('/crypto/identity/backup', [MessengerCryptoController::class, 'updateIdentityBackup'])->name('crypto.identity.backup');
        Route::put('/crypto/identity/seamless-unlock', [MessengerCryptoController::class, 'putSeamlessUnlock'])->name('crypto.identity.seamless');
        Route::post('/crypto/identity/distribute', [MessengerCryptoController::class, 'distributeIdentity'])->name('crypto.identity.distribute');
        Route::get('/crypto/identity/packages', [MessengerCryptoController::class, 'identityPackages'])->name('crypto.identity.packages');
        Route::post('/crypto/identity/packages/ack', [MessengerCryptoController::class, 'ackIdentityPackages'])->name('crypto.identity.ack');
        Route::post('/crypto/identity/request', [MessengerCryptoController::class, 'requestIdentity'])->name('crypto.identity.request');
        Route::put('/crypto/vault', [MessengerCryptoController::class, 'upsertKeyVault'])->name('crypto.vault.upsert');
        Route::get('/crypto/vault', [MessengerCryptoController::class, 'pullKeyVault'])->name('crypto.vault.pull');
        Route::get('/media/{message}', [MessengerCryptoController::class, 'streamMedia'])->name('media.stream');
        Route::post('/media/{message}/signed-url', [MessengerCryptoController::class, 'signedMediaUrl'])->name('media.signed-url');


        // Conversations
        Route::get('/conversations', [MessengerController::class, 'conversations'])->name('conversations.index');
        Route::post('/conversations', [MessengerController::class, 'createConversation'])->name('conversations.create');
        Route::post('/saved', [MessengerController::class, 'savedConversation'])->name('saved');
        Route::get('/messages/search', [MessengerController::class, 'searchMessages'])->middleware('throttle:messenger.search')->name('messages.search');
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
        Route::get('/join/preview', [GroupChannelController::class, 'previewJoin'])->name('join.preview');

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
        Route::post('/messages/views', [GroupChannelController::class, 'viewBatch'])->name('messages.views');
        Route::post('/messages/{message}/view', [GroupChannelController::class, 'view'])->name('messages.view');

        // Messages
        Route::get('/conversations/{conversation}/messages', [MessengerController::class, 'messages'])->name('conversations.messages');
        Route::get('/conversations/{conversation}/shared-media', [MessengerController::class, 'sharedMedia'])->middleware('throttle:messenger.search')->name('conversations.shared-media');
        Route::post('/conversations/{conversation}/messages', [MessengerController::class, 'sendMessage'])->middleware('throttle:messenger.send')->name('conversations.messages.send');
        Route::post('/conversations/{conversation}/media', [MessengerController::class, 'sendMedia'])->middleware('throttle:messenger.send')->name('conversations.media.send');
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
        Route::post('/contacts/sync', [ContactController::class, 'sync'])->middleware('throttle:messenger.search')->name('contacts.sync');
        Route::post('/contacts/sync/refresh', [ContactController::class, 'refreshSync'])->middleware('throttle:messenger.search')->name('contacts.sync.refresh');
        Route::get('/contacts/synced', [ContactController::class, 'synced'])->name('contacts.synced');
        Route::put('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');
        Route::get('/users/search', [ContactController::class, 'search'])->middleware('throttle:messenger.search')->name('users.search');
        Route::post('/users/{userId}/block', [ContactController::class, 'block'])->name('users.block');
        Route::post('/users/{userId}/unblock', [ContactController::class, 'unblock'])->name('users.unblock');

        // Settings & profiles
        Route::get('/settings', [MessengerController::class, 'getSettings'])->name('settings.show');
        Route::put('/settings', [MessengerController::class, 'updateSettings'])->name('settings.update');
        Route::get('/wallpapers', [MessengerController::class, 'listWallpapers'])->name('wallpapers.index');
        Route::post('/wallpapers', [MessengerController::class, 'uploadWallpaper'])->middleware('throttle:messenger.send')->name('wallpapers.upload');
        Route::delete('/wallpapers/{wallpaper}', [MessengerController::class, 'deleteWallpaper'])->middleware('throttle:messenger.destructive')->name('wallpapers.delete');

        // Sticker packs (static-hosted assets; install by pack id per user)
        Route::get('/sticker-packs', [StickerPackController::class, 'index'])->name('sticker-packs.index');
        Route::post('/sticker-packs', [StickerPackController::class, 'store'])->middleware('throttle:messenger.send')->name('sticker-packs.store');
        Route::get('/sticker-packs/{uuid}', [StickerPackController::class, 'show'])->name('sticker-packs.show');
        Route::put('/sticker-packs/{uuid}', [StickerPackController::class, 'updatePack'])->name('sticker-packs.update');
        Route::delete('/sticker-packs/{uuid}', [StickerPackController::class, 'destroyPack'])->middleware('throttle:messenger.destructive')->name('sticker-packs.destroy');
        Route::post('/sticker-packs/{uuid}/stickers', [StickerPackController::class, 'addStickers'])->middleware('throttle:messenger.send')->name('sticker-packs.stickers.add');
        Route::post('/sticker-packs/{uuid}/install', [StickerPackController::class, 'install'])->name('sticker-packs.install');
        Route::delete('/sticker-packs/{uuid}/install', [StickerPackController::class, 'uninstall'])->name('sticker-packs.uninstall');
        Route::put('/stickers/{stickerUuid}', [StickerPackController::class, 'updateSticker'])->name('stickers.update');
        Route::delete('/stickers/{stickerUuid}', [StickerPackController::class, 'destroySticker'])->middleware('throttle:messenger.destructive')->name('stickers.destroy');

        Route::get('/conversations/{conversation}/wallpaper', [MessengerController::class, 'getConversationWallpaper'])->name('conversations.wallpaper.show');
        Route::put('/conversations/{conversation}/wallpaper', [MessengerController::class, 'setConversationWallpaper'])->name('conversations.wallpaper.set');
        Route::get('/me', [MessengerController::class, 'myProfile'])->name('me.show');
        Route::put('/me', [MessengerController::class, 'updateMyProfile'])->name('me.update');
        Route::get('/me/username-check', [MessengerController::class, 'checkUsername'])->middleware('throttle:messenger.search')->name('me.username-check');
        Route::get('/users/{userId}/profile', [MessengerController::class, 'userProfile'])->name('users.profile');

        // Utility
        Route::get('/unread-count', [MessengerController::class, 'unreadCount'])->name('unread-count');
        Route::get('/sync', [MessengerController::class, 'sync'])->middleware('throttle:messenger.sync')->name('sync');
    });
});
