<?php

namespace App\Http\Controllers\Api\Messenger;

use App\Events\MessageSent;
use App\Events\Typing;
use App\Events\MessageRead;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class MessengerController extends Controller
{
    public function conversations(Request $request)
    {
        $user = $request->user();

        $conversations = Conversation::whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with(['users:id,first_name,last_name', 'messages' => function ($q) {
            $q->latest()->limit(1);
        }])->get();

        return response()->json($conversations);
    }

    public function createConversation(Request $request)
    {
        $request->validate(['user_id' => 'required|integer|exists:users,id']);
        $me = $request->user();
        $otherId = (int) $request->input('user_id');

        if ($otherId === $me->id) {
            return response()->json(['message' => 'Cannot create conversation with yourself'], 422);
        }

        $conversation = Conversation::whereHas('users', function ($q) use ($me) {
            $q->where('user_id', $me->id);
        })->whereHas('users', function ($q) use ($otherId) {
            $q->where('user_id', $otherId);
        })->first();

        if (! $conversation) {
            $conversation = Conversation::create();
            $conversation->users()->attach([$me->id, $otherId]);
        }

        return response()->json($conversation);
    }

    public function messages(Request $request, Conversation $conversation)
    {
        $user = $request->user();

        if (! $conversation->users()->where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $messages = $conversation->messages()->with('user:id,first_name,last_name')->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request, Conversation $conversation)
    {
        $request->validate(['body' => 'required|string']);
        $me = $request->user();

        if (! $conversation->users()->where('user_id', $me->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $me->id,
            'body' => $request->input('body'),
        ]);

        $conversation->update(['last_message_at' => Carbon::now()]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message);
    }

    public function typing(Request $request, Conversation $conversation)
    {
        $me = $request->user();

        if (! $conversation->users()->where('user_id', $me->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        broadcast(new Typing($conversation->id, $me->id));

        return response()->json(['ok' => true]);
    }

    public function markRead(Request $request, Conversation $conversation)
    {
        $me = $request->user();

        if (! $conversation->users()->where('user_id', $me->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $updated = Message::where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $me->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        broadcast(new MessageRead($conversation->id, $me->id));

        return response()->json(['updated' => $updated]);
    }
}
