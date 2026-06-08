<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * Per-user private channel: all messenger realtime events for a user are
 * delivered here (new/updated/deleted messages, read receipts, typing).
 */
Broadcast::channel('messenger.{userId}', function ($user, $userId) {
	return (int) $user->id === (int) $userId;
});

/**
 * Private channel for conversations. Only attached users can listen.
 */
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
	return \App\Models\Conversation::where('id', $conversationId)
		->whereHas('users', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})->exists();
});







