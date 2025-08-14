<?php

namespace App\Http\Controllers\Api\Chat;

use App\Events\Chat\DeleteMessage;
use App\Events\Chat\EditMessage;
use App\Events\Chat\MessageRead;
use App\Events\Chat\NewMessage;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function getMessages($username)
    {
        $userId = auth('api')->user()->id;
        $targetUser = $username;
        $targetUserId = $targetUser->id;

        $messages = Message::where(function ($query) use ($userId, $targetUserId) {
            $query->where('sender_id', $userId)->where('receiver_id', $targetUserId)->where('deleted_by_sender', 0);
        })
            ->orWhere(function ($query) use ($userId, $targetUserId) {
                $query->where('sender_id', $targetUserId)->where('receiver_id', $userId)->where('deleted_by_receiver', 0);
            })
            ->with([
                'sender' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'username', 'profile_pic', 'cover_pic')->get();
                },
                'receiver' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'username', 'profile_pic', 'cover_pic')->get();
                },
                'replyed'
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        $now = now();
        Message::where('sender_id', $targetUserId)->where('receiver_id', $userId)->update(['read_at' => $now]);
        broadcast(new MessageRead($targetUser, auth('api')->user(), $now));
        return response()->json(['targetUser' => $targetUser, 'messages' => $messages], 200);
    }

    public function markAsRead($username)
    {
        $user = auth('api')->user();
        $targetUser = $username;
        $now = now();
        Message::where('read_at', null)->where('receiver_id', $user->id)->where('sender_id', $targetUser->id)
            ->orderBy('id', 'desc')->limit(10)->update(['read_at' => $now]);
        broadcast(new MessageRead($targetUser, auth('api')->user(), $now));
        return response()->json(['message' => 'Messages marked as read successfully']);
    }

    public function chats()
    {
        $user = auth('api')->user();
        $chatUsers = Message::where('sender_id', $user->id)
            ->orWhere('receiver_id', $user->id)
            ->groupBy('sender_id', 'receiver_id')
            ->distinct()
            ->select('sender_id', 'receiver_id')
            ->get();
        $senders_id = $chatUsers->pluck('sender_id')->toArray();
        $receiver_id = $chatUsers->pluck('receiver_id')->toArray();
        $userIds = array_merge($senders_id, $receiver_id);
        $userIds = array_diff($userIds, [$user->id]);
        // dd($usersId);
        $chats = User::whereIn('id', $userIds)->select('id', 'first_name', 'last_name', 'username', 'profile_pic', 'cover_pic')->get();
        foreach ($chats as $chat) {
            $chat['last_message'] = Message::query()
                ->where('sender_id',  $user->id)
                ->where('receiver_id',  $chat['id'])
                ->orWhere('receiver_id',  $user->id)
                ->where('sender_id', $chat['id'])->orderBy('id', 'desc')->first();
            $chat['unreadCount'] = Message::query()
                ->where('read_at', null)
                // ->where('sender_id',  $user->id)
                // ->where('receiver_id',  $chat['id'])
                // ->orWhere('receiver_id',  $user->id)
                ->where('receiver_id',  $user->id)
                ->where('sender_id', $chat['id'])

                ->count();
        }

        return response()->json(['chats' => $chats], 200);
    }

    public function sendMessage(Request $request, $username)
    {
        $user = auth('api')->user();

        $replyed_id = null;
        if ($request->input('replyed_id')) {
            $replyed_id = $request->input('replyed_id');
        }

        $messageRaw = Message::create([
            'content' => $request->input('content'),
            'replyed_id' => $replyed_id,
            // 'read_at' => null,
            'sender_id' => $user->id,
            'receiver_id' => $username->id,
        ]);

        $message = Message::where('id', $messageRaw->id)
            ->with([
                'sender' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'username', 'profile_pic', 'cover_pic')->get();
                },
                'receiver' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'username', 'profile_pic', 'cover_pic')->get();
                },
                'replyed'
            ])
            ->first();

        // event(new NewMessage($message));
        broadcast(new NewMessage($message))->toOthers();
        return response()->json(['status' => 'Message Sent!', 'message' => $message], 200);
    }

    public function deleteMessage(Request $request, Message $message)
    {
        $user = auth('api')->user();

        if ($message->sender_id == $user->id || $message->receiver_id == $user->id) {
            if ($request->input('deleteForBoth')) {
                broadcast(new DeleteMessage($message))->toOthers();
                $message->delete();
                Message::where('replyed_id', $message->id)->update([
                    'replyed_id' => null
                ]);
            } else {
                if ($message->sender_id == $user->id) {
                    $message->deleted_by_sender = true;
                    $message->save();
                } else if ($message->receiver_id == $user->id) {
                    $message->deleted_by_receiver = true;
                    $message->save();
                }
            }

            return response()->json(['status' => 'message successfully deleted!'], 200);
        }
    }

    public function editMessage(Request $request, Message $message)
    {
        $user = auth('api')->user();
        $now = now();
        if ($message->sender_id == $user->id) {
            if($request->input('content') != $message->content){
                $message->update([
                    'content' => $request->input('content'),
                    'edited_at' => $now
                ]);

                broadcast(new EditMessage($message))->toOthers();
            }
            $message->load('sender');
            $message->load('receiver');
            $message->load('replyed');
            return response()->json(['status' => 'Message successfully edited!', 'message' => $message], 200);
        }
    }
}
