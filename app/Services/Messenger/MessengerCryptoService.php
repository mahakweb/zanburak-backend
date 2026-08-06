<?php

namespace App\Services\Messenger;

use App\Events\Messenger\MessengerBroadcast;
use App\Models\Conversation;
use App\Models\MessengerCryptoDevice;
use App\Models\MessengerE2ePackage;
use App\Models\MessengerEvent;
use App\Models\MessengerOneTimePrekey;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Server-side E2E helpers: device registry + opaque key-package relay.
 * The server never sees private keys or plaintext message bodies.
 */
class MessengerCryptoService
{
    public function __construct(
        protected RealtimeBus $bus,
        protected MessengerOutbox $outbox
    ) {}

    public function e2eEnabled(): bool
    {
        return (bool) config('messenger.e2e.enabled', true);
    }

    public function conversationShouldEncrypt(Conversation $conversation): bool
    {
        if (! $this->e2eEnabled()) {
            return false;
        }

        // Public channels are intentionally readable by joiners/server search.
        if ($conversation->isChannel() && $conversation->is_public) {
            return false;
        }

        return in_array($conversation->type, [
            Conversation::TYPE_PRIVATE,
            Conversation::TYPE_SAVED,
            Conversation::TYPE_GROUP,
            Conversation::TYPE_CHANNEL,
        ], true);
    }

    public function registerDevice(User $user, array $payload): MessengerCryptoDevice
    {
        $deviceId = (string) $payload['device_id'];
        $prekeys = $payload['one_time_prekeys'] ?? [];
        $notifySiblings = false;

        $result = DB::transaction(function () use ($user, $payload, $deviceId, $prekeys, &$notifySiblings) {
            $wasNew = ! MessengerCryptoDevice::query()
                ->where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->whereNull('revoked_at')
                ->exists();

            $device = MessengerCryptoDevice::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_id' => $deviceId,
                ],
                [
                    'label' => isset($payload['label']) ? mb_substr((string) $payload['label'], 0, 120) : null,
                    'identity_public_key' => (string) $payload['identity_public_key'],
                    'signed_prekey_id' => (int) $payload['signed_prekey_id'],
                    'signed_prekey_public' => (string) $payload['signed_prekey_public'],
                    'signed_prekey_signature' => (string) $payload['signed_prekey_signature'],
                    'last_seen_at' => now(),
                    'revoked_at' => null,
                ]
            );

            if (is_array($prekeys) && $prekeys !== []) {
                $this->replaceOneTimePrekeys($device, $prekeys);
            }

            $notifySiblings = $wasNew || $device->wasRecentlyCreated;

            return $device->fresh();
        });

        // Sibling devices must redistribute conversation keys so this device
        // can decrypt history (Saved Messages / multi-device private chats).
        // Also notify peers sharing a conversation so their next send includes
        // wraps for the new device (avoids «پیام رمزنگاری‌شده» on the new laptop).
        if ($notifySiblings) {
            $this->emitCryptoEvent((int) $user->id, [
                'type' => 'e2e.device_added',
                'device_id' => $deviceId,
                'user_id' => (int) $user->id,
            ]);

            $peerIds = Conversation::query()
                ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                ->with(['users:id'])
                ->get()
                ->flatMap(fn (Conversation $c) => $c->users->pluck('id'))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->reject(fn ($id) => $id === (int) $user->id)
                ->values();

            foreach ($peerIds as $peerId) {
                $this->emitCryptoEvent($peerId, [
                    'type' => 'e2e.device_added',
                    'device_id' => $deviceId,
                    'user_id' => (int) $user->id,
                    'peer' => true,
                ]);
            }
        }

        return $result;
    }

    public function uploadPrekeys(User $user, string $deviceId, array $prekeys): int
    {
        $device = $this->requireOwnDevice($user, $deviceId);
        $this->replaceOneTimePrekeys($device, $prekeys, false);

        return MessengerOneTimePrekey::query()
            ->where('device_row_id', $device->id)
            ->whereNull('consumed_at')
            ->count();
    }

    public function touchDevice(User $user, string $deviceId): void
    {
        MessengerCryptoDevice::query()
            ->where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->whereNull('revoked_at')
            ->update(['last_seen_at' => now()]);
    }

    public function revokeDevice(User $user, string $deviceId): void
    {
        $device = $this->requireOwnDevice($user, $deviceId);
        $device->update(['revoked_at' => now()]);
        MessengerOneTimePrekey::query()
            ->where('device_row_id', $device->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }

    public function listMyDevices(User $user): Collection
    {
        return MessengerCryptoDevice::query()
            ->where('user_id', $user->id)
            ->active()
            ->orderByDesc('last_seen_at')
            ->get()
            ->map(fn (MessengerCryptoDevice $d) => [
                'device_id' => $d->device_id,
                'label' => $d->label,
                'last_seen_at' => $d->last_seen_at?->toIso8601String(),
                'created_at' => $d->created_at?->toIso8601String(),
                'prekey_count' => $d->oneTimePrekeys()->whereNull('consumed_at')->count(),
            ]);
    }

    /**
     * Public key bundles for every active device of the given users.
     * Peeks (does not consume) one unused OTP so distribute can wrap to it;
     * OTP is marked consumed only when a package references its prekey_id.
     *
     * @param  int[]  $userIds
     */
    public function bundlesForUsers(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if ($userIds === []) {
            return [];
        }

        $devices = MessengerCryptoDevice::query()
            ->whereIn('user_id', $userIds)
            ->active()
            ->get();

        $out = [];
        foreach ($devices as $device) {
            $otp = $this->peekOneTimePrekey($device);
            $out[] = $device->toPublicBundle($otp);
        }

        return $out;
    }

    public function bundlesForConversation(User $viewer, Conversation $conversation): array
    {
        $this->assertParticipant($viewer, $conversation);
        $userIds = $conversation->users()
            ->whereNull('conversation_user.deleted_at')
            ->pluck('users.id')
            ->all();

        return $this->bundlesForUsers($userIds);
    }

    /**
     * Relay opaque conversation-key packages to recipient devices.
     *
     * @param  array<int, array{recipient_device_id:string,recipient_user_id:int,ciphertext:string,key_version:int}>  $packages
     */
    public function distributePackages(User $sender, Conversation $conversation, string $senderDeviceId, array $packages): int
    {
        $this->assertParticipant($sender, $conversation);
        $this->requireOwnDevice($sender, $senderDeviceId);

        $maxVersion = (int) $conversation->e2e_key_version;
        $created = 0;
        $notifyByUser = [];

        DB::transaction(function () use ($sender, $conversation, $senderDeviceId, $packages, &$maxVersion, &$created, &$notifyByUser) {
            foreach ($packages as $pkg) {
                $recipientDeviceId = (string) ($pkg['recipient_device_id'] ?? '');
                $recipientUserId = (int) ($pkg['recipient_user_id'] ?? 0);
                $ciphertext = (string) ($pkg['ciphertext'] ?? '');
                $keyVersion = (int) ($pkg['key_version'] ?? 0);

                if ($recipientDeviceId === '' || $recipientUserId < 1 || $ciphertext === '' || $keyVersion < 1) {
                    continue;
                }

                // Recipient must be a conversation participant.
                if (! $conversation->users()->where('users.id', $recipientUserId)->whereNull('conversation_user.deleted_at')->exists()) {
                    continue;
                }

                // Recipient device must exist and belong to that user.
                $ok = MessengerCryptoDevice::query()
                    ->where('user_id', $recipientUserId)
                    ->where('device_id', $recipientDeviceId)
                    ->active()
                    ->exists();
                if (! $ok) {
                    continue;
                }

                MessengerE2ePackage::query()->create([
                    'conversation_id' => $conversation->id,
                    'sender_user_id' => $sender->id,
                    'sender_device_id' => $senderDeviceId,
                    'recipient_device_id' => $recipientDeviceId,
                    'recipient_user_id' => $recipientUserId,
                    'key_version' => $keyVersion,
                    'ciphertext' => $ciphertext,
                ]);

                // Consume the OTP referenced in the wrap payload (if any).
                $this->consumePrekeyFromPackageCiphertext($recipientUserId, $recipientDeviceId, $ciphertext);

                $maxVersion = max($maxVersion, $keyVersion);
                $created++;
                $notifyByUser[$recipientUserId] = true;
            }

            if ($maxVersion > (int) $conversation->e2e_key_version || ! $conversation->is_encrypted) {
                $conversation->forceFill([
                    'is_encrypted' => true,
                    'e2e_key_version' => $maxVersion,
                ])->save();
            }
        });

        foreach (array_keys($notifyByUser) as $uid) {
            $this->emitCryptoEvent((int) $uid, [
                'type' => 'e2e_package',
                'conversation_id' => $conversation->id,
            ]);
        }

        return $created;
    }

    /**
     * Ask every other participant (all their devices' holders) to redistribute
     * the conversation key so this device can decrypt locked history.
     */
    public function requestConversationKey(User $user, Conversation $conversation): void
    {
        $this->assertParticipant($user, $conversation);

        $participants = $conversation->users()->get();
        foreach ($participants as $participant) {
            if ((int) $participant->id === (int) $user->id) {
                // Still notify own siblings — they may hold the key this device lacks.
            }
            $this->emitCryptoEvent((int) $participant->id, [
                'type' => 'e2e.key_request',
                'conversation_id' => (int) $conversation->id,
                'requester_user_id' => (int) $user->id,
            ]);
        }
    }

    public function pullPackages(User $user, string $deviceId, ?int $conversationId = null): array
    {
        $this->requireOwnDevice($user, $deviceId);

        $q = MessengerE2ePackage::query()
            ->where('recipient_user_id', $user->id)
            ->where('recipient_device_id', $deviceId)
            ->whereNull('consumed_at')
            ->orderBy('id');

        if ($conversationId) {
            $q->where('conversation_id', $conversationId);
        }

        $rows = $q->limit(200)->get();

        return $rows->map(fn (MessengerE2ePackage $p) => [
            'id' => $p->id,
            'conversation_id' => $p->conversation_id,
            'sender_user_id' => $p->sender_user_id,
            'sender_device_id' => $p->sender_device_id,
            'key_version' => $p->key_version,
            'ciphertext' => $p->ciphertext,
            'created_at' => $p->created_at?->toIso8601String(),
        ])->all();
    }

    public function ackPackages(User $user, string $deviceId, array $ids): int
    {
        $this->requireOwnDevice($user, $deviceId);
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return 0;
        }

        return MessengerE2ePackage::query()
            ->where('recipient_user_id', $user->id)
            ->where('recipient_device_id', $deviceId)
            ->whereIn('id', $ids)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }

    public function safetyNumberMaterial(User $viewer, int $otherUserId): array
    {
        $my = MessengerCryptoDevice::query()
            ->where('user_id', $viewer->id)
            ->active()
            ->orderBy('id')
            ->pluck('identity_public_key')
            ->all();

        $their = MessengerCryptoDevice::query()
            ->where('user_id', $otherUserId)
            ->active()
            ->orderBy('id')
            ->pluck('identity_public_key')
            ->all();

        return [
            'local_identity_keys' => $my,
            'remote_identity_keys' => $their,
        ];
    }

    protected function replaceOneTimePrekeys(MessengerCryptoDevice $device, array $prekeys, bool $replaceAll = true): void
    {
        if ($replaceAll) {
            MessengerOneTimePrekey::query()
                ->where('device_row_id', $device->id)
                ->whereNull('consumed_at')
                ->delete();
        }

        $rows = [];
        $now = Carbon::now();
        foreach ($prekeys as $pk) {
            $id = (int) ($pk['prekey_id'] ?? 0);
            $pub = (string) ($pk['public_key'] ?? '');
            if ($id < 1 || $pub === '') {
                continue;
            }
            $rows[] = [
                'device_row_id' => $device->id,
                'prekey_id' => $id,
                'public_key' => $pub,
                'signature' => isset($pk['signature']) ? (string) $pk['signature'] : null,
                'consumed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            MessengerOneTimePrekey::query()->upsert(
                $chunk,
                ['device_row_id', 'prekey_id'],
                ['public_key', 'signature', 'consumed_at', 'updated_at']
            );
        }
    }

    /** Peek next unused OTP without consuming it. */
    protected function peekOneTimePrekey(MessengerCryptoDevice $device): ?array
    {
        $row = MessengerOneTimePrekey::query()
            ->where('device_row_id', $device->id)
            ->whereNull('consumed_at')
            ->orderBy('prekey_id')
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'prekey_id' => (int) $row->prekey_id,
            'public_key' => $row->public_key,
            'signature' => $row->signature,
        ];
    }

    /**
     * Mark an OTP consumed when a distribute package references it.
     */
    protected function consumePrekeyFromPackageCiphertext(int $recipientUserId, string $recipientDeviceId, string $ciphertext): void
    {
        $decoded = json_decode($ciphertext, true);
        if (! is_array($decoded)) {
            return;
        }
        $prekeyId = (int) ($decoded['prekey_id'] ?? 0);
        if ($prekeyId < 1) {
            return;
        }

        $device = MessengerCryptoDevice::query()
            ->where('user_id', $recipientUserId)
            ->where('device_id', $recipientDeviceId)
            ->active()
            ->first();
        if (! $device) {
            return;
        }

        MessengerOneTimePrekey::query()
            ->where('device_row_id', $device->id)
            ->where('prekey_id', $prekeyId)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }

    protected function consumeOneTimePrekey(MessengerCryptoDevice $device): ?array
    {
        return DB::transaction(function () use ($device) {
            $row = MessengerOneTimePrekey::query()
                ->where('device_row_id', $device->id)
                ->whereNull('consumed_at')
                ->orderBy('prekey_id')
                ->lockForUpdate()
                ->first();

            if (! $row) {
                return null;
            }

            $row->update(['consumed_at' => now()]);

            return [
                'prekey_id' => (int) $row->prekey_id,
                'public_key' => $row->public_key,
                'signature' => $row->signature,
            ];
        });
    }

    protected function requireOwnDevice(User $user, string $deviceId): MessengerCryptoDevice
    {
        $device = MessengerCryptoDevice::query()
            ->where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->active()
            ->first();

        if (! $device) {
            throw new \InvalidArgumentException('Unknown or revoked crypto device');
        }

        $device->forceFill(['last_seen_at' => now()])->save();

        return $device;
    }

    protected function assertParticipant(User $user, Conversation $conversation): void
    {
        $ok = $conversation->users()
            ->where('users.id', $user->id)
            ->whereNull('conversation_user.deleted_at')
            ->exists();

        if (! $ok) {
            throw new \RuntimeException('Not a participant');
        }
    }

    protected function emitCryptoEvent(int $userId, array $payload): void
    {
        $conversationId = isset($payload['conversation_id']) ? (int) $payload['conversation_id'] : null;
        // Wire event type (dot) may differ from legacy payload.type (underscore).
        $rawType = (string) ($payload['type'] ?? 'e2e.package');
        $type = match ($rawType) {
            'e2e_package', 'e2e.package' => 'e2e.package',
            'e2e.device_added', 'e2e_device_added' => 'e2e.device_added',
            'e2e.key_request', 'e2e_key_request' => 'e2e.key_request',
            default => $rawType,
        };

        if ($this->outbox->isActive()) {
            $this->outbox->enqueue([
                'op' => 'event.create',
                'user_id' => $userId,
                'conversation_id' => $conversationId,
                'type' => $type,
                'payload' => $payload,
                'created_at' => now()->toDateTimeString(),
            ]);
        } else {
            MessengerEvent::query()->create([
                'user_id' => $userId,
                'conversation_id' => $conversationId,
                'type' => $type,
                'payload' => $payload,
                'created_at' => now(),
            ]);
        }

        try {
            broadcast(new MessengerBroadcast($userId, $type, $payload, 0));
        } catch (\Throwable $e) {
            // Realtime is best-effort; packages remain pullable via API.
        }
    }
}
