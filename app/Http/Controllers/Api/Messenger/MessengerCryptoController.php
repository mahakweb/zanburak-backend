<?php

namespace App\Http\Controllers\Api\Messenger;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Messenger\MessengerCryptoService;
use App\Services\Messenger\MessengerMediaDeliveryService;
use App\Services\Messenger\MessengerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessengerCryptoController extends Controller
{
    public function __construct(
        protected MessengerCryptoService $crypto,
        protected MessengerService $messenger,
        protected MessengerMediaDeliveryService $mediaDelivery,
    ) {}

    public function registerDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string|max:64',
            'label' => 'sometimes|nullable|string|max:120',
            'identity_public_key' => 'required|string|max:8192',
            'signed_prekey_id' => 'required|integer|min:1',
            'signed_prekey_public' => 'required|string|max:4096',
            'signed_prekey_signature' => 'required|string|max:8192',
            'one_time_prekeys' => 'sometimes|array|max:200',
            'one_time_prekeys.*.prekey_id' => 'required_with:one_time_prekeys|integer|min:1',
            'one_time_prekeys.*.public_key' => 'required_with:one_time_prekeys|string|max:4096',
            'one_time_prekeys.*.signature' => 'sometimes|nullable|string|max:8192',
        ]);

        $device = $this->crypto->registerDevice($request->user(), $data);

        return response()->json([
            'device_id' => $device->device_id,
            'registered' => true,
        ]);
    }

    public function uploadPrekeys(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string|max:64',
            'one_time_prekeys' => 'required|array|min:1|max:200',
            'one_time_prekeys.*.prekey_id' => 'required|integer|min:1',
            'one_time_prekeys.*.public_key' => 'required|string|max:4096',
            'one_time_prekeys.*.signature' => 'sometimes|nullable|string|max:8192',
        ]);

        try {
            $count = $this->crypto->uploadPrekeys(
                $request->user(),
                $data['device_id'],
                $data['one_time_prekeys']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['prekey_count' => $count]);
    }

    public function myDevices(Request $request): JsonResponse
    {
        return response()->json([
            'devices' => $this->crypto->listMyDevices($request->user()),
        ]);
    }

    public function revokeDevice(Request $request, string $deviceId): JsonResponse
    {
        try {
            $this->crypto->revokeDevice($request->user(), $deviceId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['revoked' => true]);
    }

    public function bundles(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $bundles = $this->crypto->bundlesForConversation($request->user(), $conversation);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['bundles' => $bundles]);
    }

    public function distribute(Request $request, Conversation $conversation): JsonResponse
    {
        $data = $request->validate([
            'sender_device_id' => 'required|string|max:64',
            'packages' => 'required|array|min:1|max:500',
            'packages.*.recipient_device_id' => 'required|string|max:64',
            'packages.*.recipient_user_id' => 'required|integer|min:1',
            'packages.*.key_version' => 'required|integer|min:1',
            'packages.*.ciphertext' => 'required|string|max:16384',
        ]);

        try {
            $count = $this->crypto->distributePackages(
                $request->user(),
                $conversation,
                $data['sender_device_id'],
                $data['packages']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json([
            'distributed' => $count,
            'is_encrypted' => (bool) $conversation->fresh()->is_encrypted,
            'e2e_key_version' => (int) $conversation->fresh()->e2e_key_version,
        ]);
    }

    public function requestKey(Request $request, Conversation $conversation): JsonResponse
    {
        try {
            $this->crypto->requestConversationKey($request->user(), $conversation);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['requested' => true]);
    }

    public function packages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string|max:64',
            'conversation_id' => 'sometimes|nullable|integer',
        ]);

        try {
            $rows = $this->crypto->pullPackages(
                $request->user(),
                $data['device_id'],
                isset($data['conversation_id']) ? (int) $data['conversation_id'] : null
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['packages' => $rows]);
    }

    public function ackPackages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string|max:64',
            'ids' => 'required|array|min:1|max:200',
            'ids.*' => 'integer|min:1',
        ]);

        try {
            $n = $this->crypto->ackPackages($request->user(), $data['device_id'], $data['ids']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['acked' => $n]);
    }

    public function safetyNumber(Request $request, int $userId): JsonResponse
    {
        if ($userId < 1) {
            return response()->json(['message' => 'Invalid user'], 422);
        }

        return response()->json($this->crypto->safetyNumberMaterial($request->user(), $userId));
    }

    public function getUserIdentity(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        $material = $this->crypto->getUserIdentity(
            $request->user(),
            $userId !== null ? (int) $userId : null
        );

        return response()->json(['identity' => $material]);
    }

    public function publishUserIdentity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'signing_public' => 'required|string|max:4096',
            'agreement_public' => 'required|string|max:4096',
            'encrypted_backup' => 'sometimes|nullable|string|max:65536',
            'backup_salt' => 'sometimes|nullable|string|max:128',
            'backup_version' => 'sometimes|integer|min:0',
            'force_reset' => 'sometimes|boolean',
        ]);

        try {
            $row = $this->crypto->publishUserIdentity($request->user(), $data);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['identity' => $row->toPublicMaterial()]);
    }

    public function updateIdentityBackup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'encrypted_backup' => 'required|string|max:65536',
            'backup_salt' => 'sometimes|nullable|string|max:128',
            'backup_version' => 'sometimes|integer|min:0',
        ]);

        try {
            $row = $this->crypto->updateIdentityBackup($request->user(), $data);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'identity' => $row->toPublicMaterial(),
            'has_backup' => true,
        ]);
    }

    public function distributeIdentity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sender_device_id' => 'required|string|max:64',
            'packages' => 'required|array|min:1|max:50',
            'packages.*.recipient_device_id' => 'required|string|max:64',
            'packages.*.ciphertext' => 'required|string|max:65536',
        ]);

        try {
            $count = $this->crypto->distributeIdentityPackages(
                $request->user(),
                $data['sender_device_id'],
                $data['packages']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['distributed' => $count]);
    }

    public function identityPackages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string|max:64',
        ]);

        try {
            $rows = $this->crypto->pullIdentityPackages($request->user(), $data['device_id']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['packages' => $rows]);
    }

    public function ackIdentityPackages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string|max:64',
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'integer|min:1',
        ]);

        try {
            $n = $this->crypto->ackIdentityPackages(
                $request->user(),
                $data['device_id'],
                $data['ids']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['acked' => $n]);
    }

    public function requestIdentity(Request $request): JsonResponse
    {
        $data = $request->validate([
            'device_id' => 'required|string|max:64',
        ]);

        try {
            $this->crypto->requestIdentityTransfer($request->user(), $data['device_id']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['requested' => true]);
    }

    /**
     * Upload opaque identity-wrapped conversation keys for multi-device recovery.
     */
    public function upsertKeyVault(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sender_device_id' => 'required|string|max:64',
            'entries' => 'required|array|min:1|max:200',
            'entries.*.conversation_id' => 'required|integer|min:1',
            'entries.*.key_version' => 'required|integer|min:1',
            'entries.*.ciphertext' => 'required|string|max:16384',
        ]);

        try {
            $count = $this->crypto->upsertKeyVault(
                $request->user(),
                $data['sender_device_id'],
                $data['entries']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['stored' => $count]);
    }

    /**
     * Pull identity-wrapped conversation keys so a new device can decrypt history.
     */
    public function pullKeyVault(Request $request): JsonResponse
    {
        $data = $request->validate([
            'conversation_id' => 'sometimes|nullable|integer',
        ]);

        $rows = $this->crypto->pullKeyVault(
            $request->user(),
            isset($data['conversation_id']) ? (int) $data['conversation_id'] : null
        );

        return response()->json(['entries' => $rows]);
    }

    /**
     * Authenticated media proxy — never expose raw CDN URLs for chat media.
     * Supports file|thumb|cover|hls variants, HTTP Range, and cache/ETag headers.
     */
    public function streamMedia(Request $request, Message $message): StreamedResponse|JsonResponse
    {
        $variant = (string) $request->query('v', MessengerMediaDeliveryService::VARIANT_FILE);
        $target = $this->mediaDelivery->resolveTarget($request->user(), $message, $variant);
        if ($target instanceof JsonResponse) {
            return $target;
        }

        // HLS segment under the playlist folder: ?v=hls&path=360p/segment000.ts
        if ($variant === MessengerMediaDeliveryService::VARIANT_HLS && $request->filled('path')) {
            return $this->mediaDelivery->streamHlsAsset($request, $target, (string) $request->query('path'));
        }

        return $this->mediaDelivery->stream($request, $target);
    }

    /**
     * Issue a short-lived signed URL (CDN / edge friendly, no Bearer on byte range).
     */
    public function signedMediaUrl(Request $request, Message $message): JsonResponse
    {
        $data = $request->validate([
            'v' => 'sometimes|string|in:file,thumb,cover,hls',
            'ttl' => 'sometimes|integer|min:1|max:60',
        ]);

        $result = $this->mediaDelivery->issueSignedUrl(
            $request->user(),
            $message,
            $data['v'] ?? MessengerMediaDeliveryService::VARIANT_FILE,
            (int) ($data['ttl'] ?? config('messenger.media.signed_url_ttl', 20))
        );

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->json($result);
    }

    /**
     * Temporary signed access — validates visibility for the uid embedded in the signature.
     */
    public function streamSignedMedia(Request $request, Message $message): StreamedResponse|JsonResponse
    {
        $uid = (int) $request->query('uid');
        $user = User::query()->find($uid);
        if (! $user) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $variant = (string) $request->query('v', MessengerMediaDeliveryService::VARIANT_FILE);
        $target = $this->mediaDelivery->resolveTarget($user, $message, $variant);
        if ($target instanceof JsonResponse) {
            return $target;
        }

        if ($variant === MessengerMediaDeliveryService::VARIANT_HLS && $request->filled('path')) {
            return $this->mediaDelivery->streamHlsAsset($request, $target, (string) $request->query('path'));
        }

        return $this->mediaDelivery->stream($request, $target);
    }
}
