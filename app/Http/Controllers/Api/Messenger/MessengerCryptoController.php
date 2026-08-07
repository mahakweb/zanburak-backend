<?php

namespace App\Http\Controllers\Api\Messenger;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\Messenger\MessengerCryptoService;
use App\Services\Messenger\MessengerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessengerCryptoController extends Controller
{
    public function __construct(
        protected MessengerCryptoService $crypto,
        protected MessengerService $messenger
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
     * Authenticated media proxy — never expose raw CDN URLs for chat media.
     */
    public function streamMedia(Request $request, Message $message): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        $variant = $request->query('v', 'file'); // file|thumb|cover

        try {
            $visible = Message::query()
                ->visibleTo($user)
                ->whereKey($message->id)
                ->firstOrFail();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $meta = is_array($visible->meta) ? $visible->meta : [];
        $pathKey = match ($variant) {
            'thumb' => 'thumb_path',
            'cover' => 'cover_path',
            default => 'path',
        };
        $legacyUrlKey = match ($variant) {
            'thumb' => 'thumb_url',
            'cover' => 'cover_url',
            default => 'url',
        };

        $diskName = $meta['disk'] ?? config('messenger.media.disk', 'static');
        $path = $meta[$pathKey] ?? null;

        // Legacy public-URL messages: map URL → disk path when possible.
        if ((! is_string($path) || $path === '') && ! empty($meta[$legacyUrlKey])) {
            $path = $this->pathFromPublicUrl((string) $meta[$legacyUrlKey], $diskName);
        }

        if (! is_string($path) || $path === '') {
            return response()->json(['message' => 'Media missing'], 404);
        }

        // Only allow messenger media folders (public chats + private).
        $allowedPrefixes = [
            trim((string) config('messenger.media.folder', 'images/messenger/chats'), '/'),
            trim((string) config('messenger.media.private_folder', 'private/messenger'), '/'),
        ];
        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        $okPrefix = false;
        foreach ($allowedPrefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($normalized, $prefix.'/')) {
                $okPrefix = true;
                break;
            }
        }
        if (! $okPrefix) {
            return response()->json(['message' => 'Forbidden path'], 403);
        }

        $disk = Storage::disk($diskName);
        try {
            if (! $disk->exists($normalized)) {
                return response()->json(['message' => 'File not found'], 404);
            }
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Storage unavailable'], 503);
        }

        $mime = $meta['mime'] ?? 'application/octet-stream';
        if (! empty($meta['encrypted']) || ! empty($visible->is_encrypted)) {
            // Ciphertext blob — client decrypts; do not advertise original mime.
            $mime = 'application/octet-stream';
        }

        $size = null;
        try {
            $size = $disk->size($normalized);
        } catch (\Throwable $e) {
            // optional
        }

        return response()->stream(function () use ($disk, $normalized) {
            $stream = $disk->readStream($normalized);
            if ($stream === false) {
                return;
            }
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, array_filter([
            'Content-Type' => $mime,
            'Content-Length' => $size !== null ? (string) $size : null,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ]));
    }

    protected function pathFromPublicUrl(string $url, string $diskName): ?string
    {
        $baseUrl = rtrim((string) config("filesystems.disks.{$diskName}.url", ''), '/');
        $normalized = strtok($url, '?') ?: $url;
        if ($baseUrl !== '' && str_starts_with($normalized, $baseUrl.'/')) {
            return ltrim(substr($normalized, strlen($baseUrl)), '/');
        }

        $folder = trim((string) config('messenger.media.folder', 'images/messenger/chats'), '/');
        $private = trim((string) config('messenger.media.private_folder', 'private/messenger'), '/');
        foreach ([$folder, $private] as $prefix) {
            $pos = strpos($normalized, '/'.$prefix.'/');
            if ($pos !== false) {
                return ltrim(substr($normalized, $pos), '/');
            }
        }

        return null;
    }
}
