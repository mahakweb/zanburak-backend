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
        $contacts = $this->messenger->listContacts($request->user());

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
}
