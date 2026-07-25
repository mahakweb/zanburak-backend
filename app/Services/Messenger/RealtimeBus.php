<?php

namespace App\Services\Messenger;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Optional Redis acceleration layer for messenger realtime delivery.
 * All methods are safe to call even when Redis is disabled or unreachable.
 */
class RealtimeBus
{
    protected ?bool $available = null;

    public function isEnabled(): bool
    {
        return (bool) config('messenger.redis.enabled', false);
    }

    public function isAvailable(): bool
    {
        if ($this->available !== null) {
            return $this->available;
        }

        if (! $this->isEnabled()) {
            return $this->available = false;
        }

        try {
            Redis::connection(config('messenger.redis.connection', 'default'))->ping();

            return $this->available = true;
        } catch (\Throwable $e) {
            Log::debug('Messenger Redis unavailable: '.$e->getMessage());

            return $this->available = false;
        }
    }

    public function wakeUser(int $userId): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        try {
            $key = $this->wakeKey($userId);
            Redis::connection()->lpush($key, '1');
            Redis::connection()->expire($key, 30);
        } catch (\Throwable $e) {
            Log::debug('Messenger Redis wake failed: '.$e->getMessage());
        }
    }

    /**
     * Block up to $seconds waiting for a wake signal. Returns true if woken.
     */
    public function waitForWake(int $userId, int $seconds = 5): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        try {
            $key = $this->wakeKey($userId);
            $result = Redis::connection()->blpop([$key], $seconds);

            return $result !== null;
        } catch (\Throwable $e) {
            Log::debug('Messenger Redis wait failed: '.$e->getMessage());

            return false;
        }
    }

    public function cacheCursor(int $userId, int $cursor): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        try {
            Redis::connection()->setex($this->cursorKey($userId), 3600, (string) $cursor);
        } catch (\Throwable $e) {
            Log::debug('Messenger Redis cursor cache failed: '.$e->getMessage());
        }
    }

    public function getCachedCursor(int $userId): ?int
    {
        if (! $this->isAvailable()) {
            return null;
        }

        try {
            $val = Redis::connection()->get($this->cursorKey($userId));

            return $val !== null ? (int) $val : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function setTyping(int $conversationId, int $userId): void
    {
        if (! $this->isAvailable()) {
            return;
        }

        try {
            $key = $this->typingKey($conversationId, $userId);
            Redis::connection()->setex($key, 3, '1');
        } catch (\Throwable $e) {
            Log::debug('Messenger Redis typing failed: '.$e->getMessage());
        }
    }

    public function isTyping(int $conversationId, int $userId): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        try {
            return (bool) Redis::connection()->exists($this->typingKey($conversationId, $userId));
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function prefix(): string
    {
        return config('messenger.redis.prefix', 'messenger');
    }

    protected function wakeKey(int $userId): string
    {
        return "{$this->prefix()}:wake:{$userId}";
    }

    protected function cursorKey(int $userId): string
    {
        return "{$this->prefix()}:cursor:{$userId}";
    }

    protected function typingKey(int $conversationId, int $userId): string
    {
        return "{$this->prefix()}:typing:{$conversationId}:{$userId}";
    }
}
