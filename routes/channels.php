<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * Private channel for conversations. Only attached users can listen.
 */
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
	return \App\Models\Conversation::where('id', $conversationId)
		->whereHas('users', function ($q) use ($user) {
			$q->where('user_id', $user->id);
		})->exists();
});







