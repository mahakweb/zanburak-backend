<?php

namespace App\Http\Controllers\Api\Messenger;

use App\Http\Controllers\Controller;
use App\Http\Resources\Messenger\ContactResource;
use App\Http\Resources\Messenger\UserBriefResource;
use App\Models\Contact;
use App\Services\Messenger\MessengerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(
        protected MessengerService $messenger
    ) {}

    public function index(Request $request): JsonResponse
    {
        $sort = (string) $request->query('sort', 'name_asc');
        $contacts = $this->messenger->listContacts($request->user(), $sort);

        return response()->json(ContactResource::collection($contacts));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'contact_user_id' => 'required|integer|exists:users,id',
            'name' => 'sometimes|string|max:255',
        ]);

        try {
            $contact = $this->messenger->addContact(
                $request->user(),
                (int) $request->input('contact_user_id'),
                $request->input('name')
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(new ContactResource($contact), 201);
    }

    public function update(Request $request, Contact $contact): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_blocked' => 'sometimes|boolean',
            'is_favorite' => 'sometimes|boolean',
        ]);

        try {
            $contact = $this->messenger->updateContact($request->user(), $contact, $request->only([
                'name', 'is_blocked', 'is_favorite',
            ]));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(new ContactResource($contact));
    }

    public function destroy(Request $request, Contact $contact): JsonResponse
    {
        try {
            $this->messenger->deleteContact($request->user(), $contact);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['ok' => true]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2|max:100']);

        $users = $this->messenger->searchUsers(
            $request->user(),
            $request->input('q'),
            $request->integer('limit', 10)
        );

        return response()->json(UserBriefResource::collection($users));
    }

    /**
     * Resolve an identifier (email / phone / username) WITHOUT taking any
     * action, so the UI can ask the user what to do next.
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate(['identifier' => 'required|string|max:150']);

        $identifier = trim($request->input('identifier'));
        $found = $this->messenger->findUserByIdentifier($identifier);

        if ($found) {
            return response()->json([
                'status' => $found->id === $request->user()->id ? 'self' : 'found',
                'user' => new UserBriefResource($found),
            ]);
        }

        $channel = $this->messenger->detectIdentifierChannel($identifier);

        return response()->json([
            'status' => $channel ? 'can_invite' : 'invalid',
            'channel' => $channel,
        ]);
    }

    /**
     * Add a contact by email / phone / username. If the person is already a
     * member we add them; otherwise we send an invitation to register.
     */
    public function invite(Request $request): JsonResponse
    {
        $request->validate(['identifier' => 'required|string|max:150']);

        $identifier = trim($request->input('identifier'));
        $found = $this->messenger->findUserByIdentifier($identifier);

        if ($found) {
            if ($found->id === $request->user()->id) {
                return response()->json(['message' => 'Cannot add yourself'], 422);
            }

            $contact = $this->messenger->addContact($request->user(), $found->id);

            return response()->json([
                'status' => 'added',
                'contact' => new ContactResource($contact),
                'user' => new UserBriefResource($found),
            ], 201);
        }

        $channel = $this->messenger->detectIdentifierChannel($identifier);
        if (! $channel) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'User not found and the identifier is not a valid email or phone to invite.',
            ], 422);
        }

        $result = $this->messenger->sendInvite($request->user(), $identifier, $channel);

        return response()->json([
            'status' => 'invited',
            'channel' => $channel,
            'invited' => $result['invited'] ?? false,
            'throttled' => $result['throttled'] ?? false,
        ]);
    }

    public function blocked(Request $request): JsonResponse
    {
        $contacts = $this->messenger->listBlocked($request->user());

        return response()->json(ContactResource::collection($contacts));
    }

    public function block(Request $request, int $userId): JsonResponse
    {
        try {
            $contact = $this->messenger->blockUser($request->user(), $userId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(new ContactResource($contact));
    }

    public function unblock(Request $request, int $userId): JsonResponse
    {
        $this->messenger->unblockUser($request->user(), $userId);

        return response()->json(['ok' => true]);
    }
}
