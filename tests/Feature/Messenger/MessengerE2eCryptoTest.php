<?php

namespace Tests\Feature\Messenger;

use App\Models\Conversation;
use App\Models\MessengerCryptoDevice;
use App\Models\MessengerOneTimePrekey;
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
}
