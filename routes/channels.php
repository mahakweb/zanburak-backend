<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Per-user private channel: all messenger realtime events for a user are
 * delivered here (new/updated/deleted messages, read receipts, typing).
 */
Broadcast::channel('messenger.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

/**
 * Private conversation channel for WebSocket peer relay (whisper).
 * Positive Redis membership is enough; negative/miss falls back to DB so a
 * stale set cannot block Saved Messages or other legitimate members.
 */
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $uid = (int) $user->id;
    $cid = (int) $conversationId;
    $allowKey = "messenger:channel_auth:v2:{$cid}:{$uid}";

    if (Cache::get($allowKey) === true) {
        return true;
    }

    try {
        $outbox = app(\App\Services\Messenger\MessengerOutbox::class);
        if ($outbox->isCachedParticipant($cid, $uid) === true) {
            Cache::put($allowKey, true, 60);

            return true;
        }
    } catch (\Throwable $e) {
        // fall through to DB
    }

    $ok = DB::table('conversation_user')
        ->where('conversation_id', $cid)
        ->where('user_id', $uid)
        ->whereNull('deleted_at')
        ->exists();

    // Only cache allow — never cache deny (stale negatives locked Saved Messages).
    if ($ok) {
        Cache::put($allowKey, true, 60);
    }

    return $ok;
});
