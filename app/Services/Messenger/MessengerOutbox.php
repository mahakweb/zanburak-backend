<?php

namespace App\Services\Messenger;

use App\Models\Message;
use App\Models\MessengerEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Redis write-behind outbox for messenger.
 *
 * Hot path: push message/events/receipts into Redis lists (RAM), broadcast live
 * over Reverb immediately. A scheduled flusher drains the outbox into Postgres
 * in batches so clients never wait on durable I/O for delivery or ticks.
 */
class MessengerOutbox
{
    public function __construct(
        protected RealtimeBus $bus
    ) {}

    public function isActive(): bool
    {
        return (bool) config('messenger.hot_path', true) && $this->bus->isAvailable();
    }

    public function enqueue(array $item): void
    {
        if (! $this->isActive()) {
            return;
        }

        try {
            $conn = $this->redis();
            $conn->lpush($this->outboxKey(), json_encode($item, JSON_THROW_ON_ERROR));
            $conn->expire($this->outboxKey(), (int) config('messenger.outbox_ttl', 86400));
        } catch (\Throwable $e) {
            Log::warning('Messenger outbox enqueue failed: '.$e->getMessage());
            throw $e;
        }
    }

    public function pendingCount(): int
    {
        if (! $this->isActive()) {
            return 0;
        }

        try {
            return (int) $this->redis()->llen($this->outboxKey());
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function shouldFlushNow(): bool
    {
        $batch = (int) config('messenger.flush_batch_size', 20);

        return $this->pendingCount() >= $batch;
    }

    /**
     * Allocate a durable numeric message id from a Redis sequence seeded by MAX(id).
     * Shared by hot text and durable media so send order == id order.
     */
    public function allocateMessageId(): int
    {
        $key = $this->prefix().':seq:messages';
        $conn = $this->redis();

        if (! $conn->exists($key)) {
            $max = (int) (Message::withTrashed()->max('id') ?? 0);
            $conn->setnx($key, (string) $max);
        }

        return (int) $conn->incr($key);
    }

    /**
     * Raise the Redis message sequence to at least $id (after durable inserts
     * that did not go through allocateMessageId, or after Postgres setval).
     */
    public function ensureSequenceAtLeast(int $id): void
    {
        if ($id < 1 || ! $this->bus->isAvailable()) {
            return;
        }

        try {
            $key = $this->prefix().':seq:messages';
            $conn = $this->redis();
            // Lua: set key to max(current, id) without losing concurrent incrs.
            $conn->eval(
                "local cur = tonumber(redis.call('GET', KEYS[1]) or '0'); "
                ."if cur < tonumber(ARGV[1]) then redis.call('SET', KEYS[1], ARGV[1]); end; "
                .'return 1;',
                1,
                $key,
                (string) $id
            );
        } catch (\Throwable $e) {
            Log::debug('Messenger seq bump failed: '.$e->getMessage());
        }
    }

    public function rememberClientId(int $conversationId, int $userId, string $clientId, int $messageId): void
    {
        if (! $this->isActive()) {
            return;
        }

        try {
            $this->redis()->setex(
                $this->clientKey($conversationId, $userId, $clientId),
                (int) config('messenger.outbox_ttl', 86400),
                (string) $messageId
            );
        } catch (\Throwable $e) {
            Log::debug('Messenger client_id cache failed: '.$e->getMessage());
        }
    }

    public function findMessageIdByClient(int $conversationId, int $userId, string $clientId): ?int
    {
        if (! $this->isActive()) {
            return null;
        }

        try {
            $val = $this->redis()->get($this->clientKey($conversationId, $userId, $clientId));

            return $val !== null ? (int) $val : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function cacheHotMessage(array $row): void
    {
        if (! $this->isActive()) {
            return;
        }

        try {
            $conn = $this->redis();
            $ttl = (int) config('messenger.outbox_ttl', 86400);
            $conn->setex($this->hotMessageKey((int) $row['id']), $ttl, json_encode($row, JSON_THROW_ON_ERROR));
            $conn->zadd(
                $this->hotConversationKey((int) $row['conversation_id']),
                (float) $row['id'],
                (string) $row['id']
            );
            $conn->expire($this->hotConversationKey((int) $row['conversation_id']), $ttl);
        } catch (\Throwable $e) {
            Log::debug('Messenger hot message cache failed: '.$e->getMessage());
        }
    }

    public function getHotMessage(int $messageId): ?array
    {
        if (! $this->isActive()) {
            return null;
        }

        try {
            $raw = $this->redis()->get($this->hotMessageKey($messageId));

            return $raw ? json_decode($raw, true) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Pending (not-yet-flushed) messages for a conversation, oldest first.
     *
     * @return array<int, array>
     */
    public function hotMessagesForConversation(int $conversationId, ?int $beforeId = null): array
    {
        if (! $this->isActive()) {
            return [];
        }

        try {
            $ids = $this->redis()->zrange($this->hotConversationKey($conversationId), 0, -1);
            if (! $ids) {
                return [];
            }

            $rows = [];
            foreach ($ids as $id) {
                $id = (int) $id;
                if ($beforeId && $id >= $beforeId) {
                    continue;
                }
                $row = $this->getHotMessage($id);
                if ($row && empty($row['_flushed'])) {
                    $rows[] = $row;
                }
            }

            usort($rows, function ($a, $b) {
                $ta = (string) ($a['created_at'] ?? '');
                $tb = (string) ($b['created_at'] ?? '');
                if ($ta !== $tb) {
                    return $ta <=> $tb;
                }

                return ((int) $a['id']) <=> ((int) $b['id']);
            });

            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function forgetHotMessage(int $conversationId, int $messageId): void
    {
        if (! $this->isActive()) {
            return;
        }

        try {
            $conn = $this->redis();
            $conn->del($this->hotMessageKey($messageId));
            $conn->zrem($this->hotConversationKey($conversationId), (string) $messageId);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Drain up to $limit outbox items into Postgres. Returns number of ops flushed.
     */
    public function flush(int $limit = 100): int
    {
        if (! $this->bus->isAvailable()) {
            return 0;
        }

        $items = [];
        try {
            $conn = $this->redis();
            for ($i = 0; $i < $limit; $i++) {
                $raw = $conn->rpop($this->outboxKey());
                if ($raw === null) {
                    break;
                }
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $items[] = $decoded;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Messenger outbox pop failed: '.$e->getMessage());

            return 0;
        }

        if ($items === []) {
            return 0;
        }

        try {
            DB::transaction(function () use ($items) {
                foreach ($items as $item) {
                    $this->applyItem($item);
                }

                $this->syncMessageSequence();
            });
        } catch (\Throwable $e) {
            Log::error('Messenger outbox flush failed, re-queueing: '.$e->getMessage());
            // Best-effort re-queue so we do not lose durable writes.
            foreach (array_reverse($items) as $item) {
                try {
                    $this->redis()->rpush($this->outboxKey(), json_encode($item));
                } catch (\Throwable $inner) {
                    Log::error('Messenger outbox re-queue failed: '.$inner->getMessage());
                }
            }

            throw $e;
        }

        return count($items);
    }

    protected function applyItem(array $item): void
    {
        $op = $item['op'] ?? '';

        match ($op) {
            'message.create' => $this->flushMessageCreate($item),
            'event.create' => $this->flushEventCreate($item),
            'messages.read' => $this->flushMessagesRead($item),
            'message.delivered' => $this->flushMessageDelivered($item),
            default => Log::warning('Messenger outbox unknown op: '.$op),
        };
    }

    protected function flushMessageCreate(array $item): void
    {
        $row = $item['message'] ?? [];
        if ($row === []) {
            return;
        }

        $payload = [
            'id' => (int) $row['id'],
            'conversation_id' => (int) $row['conversation_id'],
            'user_id' => (int) $row['user_id'],
            'client_id' => $row['client_id'] ?? null,
            'body' => $row['body'],
            'type' => $row['type'] ?? 'text',
            'is_encrypted' => (bool) ($row['is_encrypted'] ?? false),
            'sender_device_id' => $row['sender_device_id'] ?? null,
            'e2e' => isset($row['e2e'])
                ? (is_string($row['e2e']) ? $row['e2e'] : json_encode($row['e2e']))
                : null,
            'reply_to_id' => $row['reply_to_id'] ?? null,
            'reply_show_title' => (bool) ($row['reply_show_title'] ?? true),
            'forwarded_from_user_id' => $row['forwarded_from_user_id'] ?? null,
            'is_silent' => (bool) ($row['is_silent'] ?? false),
            'scheduled_at' => $row['scheduled_at'] ?? null,
            'auto_delete_at' => $row['auto_delete_at'] ?? null,
            'view_count' => (int) ($row['view_count'] ?? 0),
            'mentions' => isset($row['mentions'])
                ? (is_string($row['mentions']) ? $row['mentions'] : json_encode($row['mentions']))
                : null,
            'meta' => isset($row['meta'])
                ? (is_string($row['meta']) ? $row['meta'] : json_encode($row['meta']))
                : null,
            'read_at' => $row['read_at'] ?? null,
            'delivered_at' => $row['delivered_at'] ?? null,
            'edited_at' => $row['edited_at'] ?? null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'deleted_at' => $row['deleted_at'] ?? null,
        ];

        DB::table('messages')->insertOrIgnore($payload);

        $conversationId = (int) $payload['conversation_id'];
        $createdAt = $payload['created_at'];

        $current = DB::table('conversations')->where('id', $conversationId)->value('last_message_at');
        if ($current === null || $createdAt >= $current) {
            DB::table('conversations')->where('id', $conversationId)->update([
                'last_message_id' => $payload['id'],
                'last_message_at' => $createdAt,
                'updated_at' => now(),
            ]);
        }

        if (! empty($item['restore_deleted'])) {
            DB::table('conversation_user')
                ->where('conversation_id', $conversationId)
                ->whereNotNull('deleted_at')
                ->update(['deleted_at' => null, 'updated_at' => now()]);
        }

        $this->forgetHotMessage($conversationId, (int) $payload['id']);
    }

    protected function flushEventCreate(array $item): void
    {
        MessengerEvent::create([
            'user_id' => (int) $item['user_id'],
            'conversation_id' => $item['conversation_id'] ?? null,
            'type' => $item['type'],
            'payload' => $item['payload'] ?? [],
            'created_at' => $item['created_at'] ?? now(),
        ]);
    }

    protected function flushMessagesRead(array $item): void
    {
        $conversationId = (int) $item['conversation_id'];
        $readerId = (int) $item['reader_id'];
        $readAt = $item['read_at'] ?? now();

        Message::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', '!=', $readerId)
            ->whereNull('read_at')
            ->update(['read_at' => $readAt]);

        DB::table('conversation_user')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $readerId)
            ->update(['last_read_at' => $readAt, 'updated_at' => now()]);
    }

    protected function flushMessageDelivered(array $item): void
    {
        $ids = array_map('intval', $item['message_ids'] ?? []);
        if ($ids === []) {
            return;
        }

        $deliveredAt = $item['delivered_at'] ?? now();

        Message::query()
            ->whereIn('id', $ids)
            ->whereNull('delivered_at')
            ->update(['delivered_at' => $deliveredAt]);
    }

    protected function syncMessageSequence(): void
    {
        try {
            $max = (int) (Message::withTrashed()->max('id') ?? 0);
            DB::statement("SELECT setval(pg_get_serial_sequence('messages', 'id'), (SELECT COALESCE(MAX(id), 1) FROM messages))");
            $this->ensureSequenceAtLeast($max);
        } catch (\Throwable $e) {
            Log::debug('Messenger sequence sync skipped: '.$e->getMessage());
        }
    }

    protected function redis()
    {
        return Redis::connection(config('messenger.redis.connection', 'default'));
    }

    protected function prefix(): string
    {
        return config('messenger.redis.prefix', 'messenger');
    }

    protected function outboxKey(): string
    {
        return $this->prefix().':outbox';
    }

    protected function clientKey(int $conversationId, int $userId, string $clientId): string
    {
        return $this->prefix().":client:{$conversationId}:{$userId}:{$clientId}";
    }

    protected function hotMessageKey(int $messageId): string
    {
        return $this->prefix().":hot:msg:{$messageId}";
    }

    protected function hotConversationKey(int $conversationId): string
    {
        return $this->prefix().":hot:conv:{$conversationId}";
    }
}
