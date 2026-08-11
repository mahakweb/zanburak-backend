<?php

namespace Tests\Feature\Messenger;

use App\Events\Messenger\MessengerBroadcast;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessengerMedia;
use App\Models\User;
use App\Services\Messenger\MessengerCryptoService;
use App\Services\Messenger\MessengerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SavedMessagesForwardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake([MessengerBroadcast::class]);
        config(['messenger.e2e.enabled' => true]);
    }

    public function test_saved_messages_is_not_an_e2e_conversation(): void
    {
        $user = User::factory()->create();
        $saved = app(MessengerService::class)->getOrCreateSavedConversation($user);

        $this->assertTrue($saved->isSaved());
        $this->assertSame($user->id, (int) $saved->owner_id);
        $this->assertFalse(
            app(MessengerCryptoService::class)->conversationShouldEncrypt($saved)
        );
    }

    public function test_forward_from_private_chat_into_saved_with_e2e_enabled(): void
    {
        [$user, $peer, $source] = $this->privateConversation();
        $msg = $this->textMessage($source, $peer, 'keep this');
        $saved = app(MessengerService::class)->getOrCreateSavedConversation($user);

        $res = $this->actingAs($user)
            ->postJson("/api/messenger/conversations/{$saved->id}/forward", [
                'message_ids' => [$msg->id],
            ]);

        $res->assertCreated()
            ->assertJsonPath('data.0.body', 'keep this')
            ->assertJsonPath('data.0.user_id', $user->id)
            ->assertJsonPath('data.0.forwarded_from.id', $peer->id)
            ->assertJsonPath('data.0.is_encrypted', false);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $saved->id,
            'user_id' => $user->id,
            'body' => 'keep this',
            'forwarded_from_user_id' => $peer->id,
        ]);
    }

    public function test_forward_from_group_and_channel_into_saved_preserves_fwd_chat_meta(): void
    {
        $user = User::factory()->create();
        $peer = User::factory()->create();
        $saved = app(MessengerService::class)->getOrCreateSavedConversation($user);

        $group = Conversation::create([
            'type' => 'group',
            'title' => 'Devs',
            'owner_id' => $user->id,
        ]);
        $group->users()->attach([
            $user->id => ['role' => 'owner', 'is_active' => true],
            $peer->id => ['role' => 'member', 'is_active' => true],
        ]);
        $groupMsg = $this->textMessage($group, $peer, 'group note');

        $channel = Conversation::create([
            'type' => 'channel',
            'title' => 'News',
            'username' => 'news_'.uniqid(),
            'is_public' => true,
            'owner_id' => $user->id,
        ]);
        $channel->users()->attach([
            $user->id => ['role' => 'owner', 'is_active' => true],
        ]);
        $channelMsg = $this->textMessage($channel, $user, 'channel post');

        $this->actingAs($user)
            ->postJson("/api/messenger/conversations/{$saved->id}/forward", [
                'message_ids' => [$groupMsg->id, $channelMsg->id],
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'data');

        $forwarded = Message::query()
            ->where('conversation_id', $saved->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $forwarded);
        $this->assertSame('Devs', $forwarded[0]->meta['fwd_chat']['title'] ?? null);
        $this->assertSame('group', $forwarded[0]->meta['fwd_chat']['type'] ?? null);
        $this->assertSame('News', $forwarded[1]->meta['fwd_chat']['title'] ?? null);
        $this->assertSame('channel', $forwarded[1]->meta['fwd_chat']['type'] ?? null);
        $this->assertSame($peer->id, (int) $forwarded[0]->forwarded_from_user_id);
        $this->assertSame($user->id, (int) $forwarded[1]->forwarded_from_user_id);
    }

    public function test_forward_media_into_saved_reuses_media_row(): void
    {
        [$user, $peer, $source] = $this->privateConversation();
        $saved = app(MessengerService::class)->getOrCreateSavedConversation($user);

        $media = MessengerMedia::createFromStoredMeta($peer->id, 'photo', [
            'disk' => config('messenger.media.disk', 'static'),
            'path' => 'private/messenger/photo/fwd-saved-reuse.jpg',
            'mime' => 'image/jpeg',
            'name' => 'photo.jpg',
            'ext' => 'jpg',
            'size' => 42,
            'width' => 10,
            'height' => 10,
        ]);

        $msg = Message::create([
            'conversation_id' => $source->id,
            'user_id' => $peer->id,
            'body' => 'caption here',
            'type' => 'photo',
            'media_id' => $media->id,
            'meta' => array_merge($media->toMessageMeta(), [
                'media_id' => $media->id,
            ]),
        ]);
        $media->bumpRef(1);
        $refsBefore = (int) $media->fresh()->ref_count;

        $this->actingAs($user)
            ->postJson("/api/messenger/conversations/{$saved->id}/forward", [
                'message_ids' => [$msg->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.0.type', 'photo')
            ->assertJsonPath('data.0.body', 'caption here')
            ->assertJsonPath('data.0.media_id', $media->id);

        $this->assertSame($refsBefore + 1, (int) $media->fresh()->ref_count);
        $this->assertSame(
            1,
            Message::query()->where('conversation_id', $saved->id)->where('media_id', $media->id)->count()
        );
    }

    public function test_forward_into_saved_works_after_swipe_hide_and_restore(): void
    {
        [$user, $peer, $source] = $this->privateConversation();
        $msg = $this->textMessage($source, $peer, 'after restore');
        $service = app(MessengerService::class);
        $saved = $service->getOrCreateSavedConversation($user);

        $service->deleteConversation($user, $saved);
        $this->assertNotNull(
            $saved->users()->where('users.id', $user->id)->first()?->pivot?->deleted_at
        );

        // Opening Saved restores membership, then forward must succeed.
        $restored = $service->getOrCreateSavedConversation($user);
        $this->assertSame($saved->id, $restored->id);

        $this->actingAs($user)
            ->postJson("/api/messenger/conversations/{$restored->id}/forward", [
                'message_ids' => [$msg->id],
            ])
            ->assertCreated()
            ->assertJsonPath('data.0.body', 'after restore');
    }

    public function test_plaintext_note_into_saved_is_allowed_while_e2e_globally_on(): void
    {
        $user = User::factory()->create();
        $saved = app(MessengerService::class)->getOrCreateSavedConversation($user);

        $this->actingAs($user)
            ->postJson("/api/messenger/conversations/{$saved->id}/messages", [
                'body' => 'my note',
                'type' => 'text',
            ])
            ->assertCreated()
            ->assertJsonPath('body', 'my note')
            ->assertJsonPath('is_encrypted', false);
    }

    public function test_get_or_create_saved_is_idempotent_and_sets_owner(): void
    {
        $user = User::factory()->create();
        $service = app(MessengerService::class);

        $a = $service->getOrCreateSavedConversation($user);
        $b = $service->getOrCreateSavedConversation($user);

        $this->assertSame($a->id, $b->id);
        $this->assertSame($user->id, (int) $b->owner_id);
        $this->assertSame(
            1,
            Conversation::query()->where('type', 'saved')->where('owner_id', $user->id)->count()
        );
    }

    public function test_forward_encrypted_source_into_saved_returns_422_not_500(): void
    {
        [$user, $peer, $source] = $this->privateConversation();
        $saved = app(MessengerService::class)->getOrCreateSavedConversation($user);
        $msg = Message::create([
            'conversation_id' => $source->id,
            'user_id' => $peer->id,
            'body' => 'ciphertext',
            'type' => 'text',
            'is_encrypted' => true,
            'e2e' => ['v' => 1, 'alg' => 'A256GCM', 'iv' => 'x', 'kid' => 1],
        ]);

        $this->actingAs($user)
            ->postJson("/api/messenger/conversations/{$saved->id}/forward", [
                'message_ids' => [$msg->id],
            ])
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Encrypted messages must be re-encrypted on the client before forwarding'
            );
    }

    /**
     * @return array{User, User, Conversation}
     */
    private function privateConversation(?User $user = null): array
    {
        $user ??= User::factory()->create();
        $other = User::factory()->create();
        $conversation = Conversation::create(['type' => 'private']);
        $conversation->users()->attach([$user->id, $other->id]);

        return [$user, $other, $conversation];
    }

    private function textMessage(Conversation $conversation, User $sender, string $body): Message
    {
        return Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'body' => $body,
            'type' => 'text',
        ]);
    }
}
