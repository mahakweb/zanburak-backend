<?php

namespace Tests\Feature\Messenger;

use App\Events\Messenger\MessengerBroadcast;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessagePin;
use App\Models\User;
use App\Services\Messenger\MessengerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MessengerCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([MessengerBroadcast::class]);
    }

    public function test_visible_scope_honors_clear_delete_for_me_soft_deletes_and_deleted_conversations(): void
    {
        [$user, $other, $conversation] = $this->conversation();
        $cutoff = now()->subMinutes(5);
        $conversation->users()->updateExistingPivot($user->id, ['cleared_at' => $cutoff]);

        $beforeClear = $this->message($conversation, $other, 'before clear', $cutoff->copy()->subMinute());
        $visible = $this->message($conversation, $other, 'visible', $cutoff->copy()->addMinute());
        $hiddenForMe = $this->message($conversation, $other, 'hidden for me', $cutoff->copy()->addMinutes(2));
        $hiddenForMe->deletedForUsers()->attach($user->id);
        $softDeleted = $this->message($conversation, $other, 'soft deleted', $cutoff->copy()->addMinutes(3));
        $softDeleted->delete();

        $this->assertSame(
            [$visible->id],
            Message::query()->visibleTo($user)->pluck('id')->all()
        );
        $this->assertNotContains($beforeClear->id, Message::query()->visibleTo($user)->pluck('id')->all());

        $conversation->users()->updateExistingPivot($user->id, ['deleted_at' => now()]);

        $this->assertFalse(Message::query()->visibleTo($user)->exists());
    }

    public function test_hidden_reply_is_not_found_and_cannot_leak_into_a_new_message(): void
    {
        [$user, $other, $conversation] = $this->conversation();
        $hidden = $this->message($conversation, $other, 'secret');
        $hidden->deletedForUsers()->attach($user->id);

        $this->actingAs($user)
            ->postJson("/api/messenger/conversations/{$conversation->id}/messages", [
                'body' => 'reply attempt',
                'reply_to_id' => $hidden->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => 'reply attempt',
        ]);
    }

    public function test_forwarding_is_atomic_when_any_source_is_invisible(): void
    {
        [$user, $sourcePeer, $source] = $this->conversation();
        [, , $target] = $this->conversation($user);
        $visible = $this->message($source, $sourcePeer, 'forward me');
        $hidden = $this->message($source, $sourcePeer, 'do not leak');
        $hidden->deletedForUsers()->attach($user->id);

        $this->actingAs($user)
            ->postJson("/api/messenger/conversations/{$target->id}/forward", [
                'message_ids' => [$visible->id, $hidden->id],
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('messages', ['conversation_id' => $target->id]);
    }

    public function test_forwarding_checks_target_blocking_before_hidden_sources(): void
    {
        [$user, $sourcePeer, $source] = $this->conversation();
        [, $targetPeer, $target] = $this->conversation($user);
        $hidden = $this->message($source, $sourcePeer, 'hidden');
        $hidden->deletedForUsers()->attach($user->id);
        Contact::create([
            'user_id' => $user->id,
            'contact_user_id' => $targetPeer->id,
            'name' => 'Blocked target',
            'is_blocked' => true,
        ]);

        $this->actingAs($user)
            ->postJson("/api/messenger/conversations/{$target->id}/forward", [
                'message_ids' => [$hidden->id],
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'You have blocked this user');
    }

    public function test_pins_unread_totals_and_mark_read_use_the_same_visibility_boundary(): void
    {
        [$user, $other, $conversation] = $this->conversation();
        $visible = $this->message($conversation, $other, 'visible unread');
        $hidden = $this->message($conversation, $other, 'hidden unread');
        $hidden->deletedForUsers()->attach($user->id);
        MessagePin::create([
            'conversation_id' => $conversation->id,
            'message_id' => $visible->id,
            'user_id' => $user->id,
            'pinned_by_id' => $user->id,
        ]);
        MessagePin::create([
            'conversation_id' => $conversation->id,
            'message_id' => $hidden->id,
            'user_id' => $user->id,
            'pinned_by_id' => $user->id,
        ]);

        $service = app(MessengerService::class);

        $this->assertSame([$visible->id], $service->pinnedMessagesFor($user, $conversation)->pluck('id')->all());
        $this->assertSame(1, $conversation->unreadCountFor($user));
        $this->assertSame(1, $service->totalUnreadCount($user));
        $this->assertSame(1, $service->markRead($user, $conversation));
        $this->assertNotNull($visible->refresh()->read_at);
        $this->assertNull($hidden->refresh()->read_at);
    }

    public function test_messenger_routes_use_named_configurable_rate_limiters(): void
    {
        $expectations = [
            'api.messenger.conversations.messages.send' => 'throttle:messenger.send',
            'api.messenger.conversations.messages.forward' => 'throttle:messenger.send',
            'api.messenger.users.search' => 'throttle:messenger.search',
            'api.messenger.contacts.lookup' => 'throttle:messenger.search',
            'api.messenger.messages.delete' => 'throttle:messenger.destructive',
            'api.messenger.conversations.typing' => 'throttle:messenger.typing',
            'api.messenger.sync' => 'throttle:messenger.sync',
            'api.messenger.presence.ping' => 'throttle:messenger.presence',
            'api.messenger.contacts.invite' => 'throttle:messenger.invite',
        ];

        foreach ($expectations as $routeName => $middleware) {
            $route = app('router')->getRoutes()->getByName($routeName);
            $this->assertNotNull($route, "Missing route {$routeName}");
            $this->assertContains($middleware, $route->gatherMiddleware());
        }

        foreach (array_unique(array_map(fn ($middleware) => substr($middleware, 19), $expectations)) as $name) {
            $this->assertGreaterThan(0, config("messenger.rate_limits.{$name}"));
        }
    }

    /**
     * @return array{User, User, Conversation}
     */
    private function conversation(?User $user = null): array
    {
        $user ??= User::factory()->create();
        $other = User::factory()->create();
        $conversation = Conversation::create(['type' => 'private']);
        $conversation->users()->attach([$user->id, $other->id]);

        return [$user, $other, $conversation];
    }

    private function message(
        Conversation $conversation,
        User $sender,
        string $body,
        $createdAt = null
    ): Message {
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
            'body' => $body,
            'type' => 'text',
        ]);

        if ($createdAt) {
            $message->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ])->saveQuietly();
        }

        return $message;
    }
}
