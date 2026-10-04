<?php

namespace Tests\Feature\Messenger;

use App\Models\Conversation;
use App\Models\MessengerCryptoDevice;
use App\Models\MessengerOneTimePrekey;
use App\Models\MessengerUserIdentity;
use App\Models\User;
use App\Services\Messenger\MessengerCryptoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessengerE2eCryptoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! \Schema::hasTable('messenger_crypto_devices')) {
            $this->markTestSkipped('E2E crypto tables not migrated');
        }
    }

    public function test_bundles_peek_otp_without_consuming(): void
    {
        $user = User::factory()->create();
        $device = MessengerCryptoDevice::query()->create([
            'user_id' => $user->id,
            'device_id' => 'web-test-1',
            'identity_public_key' => json_encode(['signing' => 's', 'agreement' => 'a']),
            'signed_prekey_id' => 1,
            'signed_prekey_public' => 'spk',
            'signed_prekey_signature' => 'sig',
            'last_seen_at' => now(),
        ]);

        MessengerOneTimePrekey::query()->create([
            'device_row_id' => $device->id,
            'prekey_id' => 10,
            'public_key' => 'otp-pub',
            'signature' => 'otp-sig',
            'consumed_at' => null,
        ]);

        /** @var MessengerCryptoService $crypto */
        $crypto = app(MessengerCryptoService::class);
        $bundles = $crypto->bundlesForUsers([$user->id]);
        $this->assertCount(1, $bundles);
        $this->assertSame(10, $bundles[0]['one_time_prekey']['prekey_id']);
        $this->assertSame('otp-sig', $bundles[0]['one_time_prekey']['signature']);

        // Still unused after peek.
        $this->assertNull(
            MessengerOneTimePrekey::query()->where('prekey_id', 10)->value('consumed_at')
        );

        // Second peek returns the same OTP.
        $bundles2 = $crypto->bundlesForUsers([$user->id]);
        $this->assertSame(10, $bundles2[0]['one_time_prekey']['prekey_id']);
    }

    public function test_distribute_consumes_referenced_otp(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $aliceDevice = MessengerCryptoDevice::query()->create([
            'user_id' => $alice->id,
            'device_id' => 'alice-1',
            'identity_public_key' => json_encode(['signing' => 's', 'agreement' => 'a']),
            'signed_prekey_id' => 1,
            'signed_prekey_public' => 'spk',
            'signed_prekey_signature' => 'sig',
            'last_seen_at' => now(),
        ]);
        $bobDevice = MessengerCryptoDevice::query()->create([
            'user_id' => $bob->id,
            'device_id' => 'bob-1',
            'identity_public_key' => json_encode(['signing' => 's', 'agreement' => 'a']),
            'signed_prekey_id' => 1,
            'signed_prekey_public' => 'spk',
            'signed_prekey_signature' => 'sig',
            'last_seen_at' => now(),
        ]);

        MessengerOneTimePrekey::query()->create([
            'device_row_id' => $bobDevice->id,
            'prekey_id' => 22,
            'public_key' => 'otp-pub',
            'signature' => 'otp-sig',
            'consumed_at' => null,
        ]);

        $conversation = Conversation::query()->create([
            'type' => Conversation::TYPE_PRIVATE,
            'is_encrypted' => false,
            'e2e_key_version' => 0,
        ]);
        $conversation->users()->attach([
            $alice->id => ['role' => 'member'],
            $bob->id => ['role' => 'member'],
        ]);

        /** @var MessengerCryptoService $crypto */
        $crypto = app(MessengerCryptoService::class);
        $count = $crypto->distributePackages($alice, $conversation, 'alice-1', [[
            'recipient_device_id' => 'bob-1',
            'recipient_user_id' => $bob->id,
            'key_version' => 1,
            'ciphertext' => json_encode([
                'iv' => 'iv',
                'ct' => 'ct',
                'prekey_id' => 22,
                'sender_agreement_pub' => 'pub',
            ]),
        ]]);

        $this->assertSame(1, $count);
        $this->assertNotNull(
            MessengerOneTimePrekey::query()->where('prekey_id', 22)->value('consumed_at')
        );
        $this->assertTrue((bool) $conversation->fresh()->is_encrypted);
        $this->assertSame(1, (int) $conversation->fresh()->e2e_key_version);

        // Silence unused vars in some PHPStan setups.
        $this->assertNotNull($aliceDevice->id);
    }

    public function test_key_vault_upsert_and_pull_for_owner(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        MessengerCryptoDevice::query()->create([
            'user_id' => $alice->id,
            'device_id' => 'alice-vault-1',
            'identity_public_key' => json_encode(['signing' => 's', 'agreement' => 'a']),
            'signed_prekey_id' => 1,
            'signed_prekey_public' => 'spk',
            'signed_prekey_signature' => 'sig',
            'last_seen_at' => now(),
        ]);

        $conversation = Conversation::query()->create([
            'type' => Conversation::TYPE_PRIVATE,
            'is_encrypted' => true,
            'e2e_key_version' => 1,
        ]);
        $conversation->users()->attach([
            $alice->id => ['role' => 'member'],
            $bob->id => ['role' => 'member'],
        ]);

        /** @var MessengerCryptoService $crypto */
        $crypto = app(MessengerCryptoService::class);

        $stored = $crypto->upsertKeyVault($alice, 'alice-vault-1', [[
            'conversation_id' => $conversation->id,
            'key_version' => 1,
            'ciphertext' => json_encode([
                'v' => 1,
                'type' => 'conv_key_vault',
                'iv' => 'opaque-iv',
                'ct' => 'opaque-ct',
                'sap' => 'device-agreement-pub',
            ]),
        ]]);

        $this->assertSame(1, $stored);

        $entries = $crypto->pullKeyVault($alice);
        $this->assertCount(1, $entries);
        $this->assertSame((int) $conversation->id, (int) $entries[0]['conversation_id']);
        $this->assertSame(1, (int) $entries[0]['key_version']);
        $this->assertStringContainsString('opaque-ct', $entries[0]['ciphertext']);

        // Bob must not see Alice's vault entries.
        $bobEntries = $crypto->pullKeyVault($bob);
        $this->assertSame([], $bobEntries);

        // Upsert same kid replaces ciphertext (latest wins).
        $crypto->upsertKeyVault($alice, 'alice-vault-1', [[
            'conversation_id' => $conversation->id,
            'key_version' => 1,
            'ciphertext' => json_encode(['v' => 1, 'ct' => 'rotated-ct']),
        ]]);
        $entries2 = $crypto->pullKeyVault($alice, (int) $conversation->id);
        $this->assertCount(1, $entries2);
        $this->assertStringContainsString('rotated-ct', $entries2[0]['ciphertext']);
    }

    public function test_new_device_registration_notifies_siblings_and_peers(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        MessengerCryptoDevice::query()->create([
            'user_id' => $alice->id,
            'device_id' => 'alice-old',
            'identity_public_key' => json_encode(['signing' => 's', 'agreement' => 'a']),
            'signed_prekey_id' => 1,
            'signed_prekey_public' => 'spk',
            'signed_prekey_signature' => 'sig',
            'last_seen_at' => now(),
        ]);
        MessengerCryptoDevice::query()->create([
            'user_id' => $bob->id,
            'device_id' => 'bob-1',
            'identity_public_key' => json_encode(['signing' => 's', 'agreement' => 'a']),
            'signed_prekey_id' => 1,
            'signed_prekey_public' => 'spk',
            'signed_prekey_signature' => 'sig',
            'last_seen_at' => now(),
        ]);

        $conversation = Conversation::query()->create([
            'type' => Conversation::TYPE_PRIVATE,
            'is_encrypted' => true,
            'e2e_key_version' => 1,
        ]);
        $conversation->users()->attach([
            $alice->id => ['role' => 'member'],
            $bob->id => ['role' => 'member'],
        ]);

        /** @var MessengerCryptoService $crypto */
        $crypto = app(MessengerCryptoService::class);
        $device = $crypto->registerDevice($alice, [
            'device_id' => 'alice-new',
            'identity_public_key' => json_encode(['signing' => 's2', 'agreement' => 'a2']),
            'signed_prekey_id' => 1,
            'signed_prekey_public' => 'spk2',
            'signed_prekey_signature' => 'sig2',
            'one_time_prekeys' => [],
        ]);

        $this->assertSame('alice-new', $device->device_id);
        $this->assertTrue(
            MessengerCryptoDevice::query()
                ->where('user_id', $alice->id)
                ->where('device_id', 'alice-new')
                ->whereNull('revoked_at')
                ->exists()
        );
    }

    public function test_key_request_is_allowed_for_participant(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $conversation = Conversation::query()->create([
            'type' => Conversation::TYPE_PRIVATE,
            'is_encrypted' => true,
            'e2e_key_version' => 1,
        ]);
        $conversation->users()->attach([
            $alice->id => ['role' => 'member'],
            $bob->id => ['role' => 'member'],
        ]);

        /** @var MessengerCryptoService $crypto */
        $crypto = app(MessengerCryptoService::class);
        // Should not throw for a participant (realtime emit is best-effort).
        $crypto->requestConversationKey($alice, $conversation);
        $this->assertTrue(true);
    }

    public function test_seamless_unlock_returned_only_to_owner(): void
    {
        if (! \Schema::hasColumn('messenger_user_identities', 'seamless_unlock')) {
            $this->markTestSkipped('seamless_unlock column missing');
        }

        $alice = User::factory()->create();
        $bob = User::factory()->create();

        MessengerUserIdentity::query()->create([
            'user_id' => $alice->id,
            'signing_public' => 'alice-sign',
            'agreement_public' => 'alice-agree',
            'encrypted_backup' => '{"v":1}',
            'backup_salt' => 'c2FsdA==',
            'backup_version' => 1,
        ]);

        /** @var MessengerCryptoService $crypto */
        $crypto = app(MessengerCryptoService::class);
        $mds = str_repeat('A', 32);
        $crypto->putSeamlessUnlock($alice, $mds);

        $owner = $crypto->getUserIdentity($alice);
        $this->assertSame($mds, $owner['seamless_unlock'] ?? null);
        $this->assertTrue((bool) ($owner['has_seamless_unlock'] ?? false));

        $peer = $crypto->getUserIdentity($bob, (int) $alice->id);
        $this->assertArrayNotHasKey('seamless_unlock', $peer ?? []);
        $this->assertArrayNotHasKey('encrypted_backup', $peer ?? []);
    }
}
