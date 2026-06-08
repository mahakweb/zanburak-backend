<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Realtime acceleration with Redis
    |--------------------------------------------------------------------------
    |
    | The messenger is fully self-hosted and works with NO external service.
    | The database "messenger_events" table is always the source of truth, so
    | everything works even without Redis. When MESSENGER_REDIS is enabled and a
    | Redis server is reachable, it is used purely to accelerate delivery
    | (instant "wake up" of open streams + O(1) cursor checks). If Redis is not
    | available at runtime, the system transparently falls back to the database.
    |
    */

    'redis' => [
        'enabled' => env('MESSENGER_REDIS', false),
        'connection' => env('MESSENGER_REDIS_CONNECTION', 'default'),
        'prefix' => env('MESSENGER_REDIS_PREFIX', 'messenger'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Server-Sent Events stream
    |--------------------------------------------------------------------------
    |
    | stream_ttl       Max lifetime (seconds) of a single SSE connection before
    |                  the client transparently reconnects (keeps PHP-FPM workers
    |                  recycling and avoids stale connections).
    | poll_interval    How often (ms) the loop checks for new events when Redis
    |                  wake-up is not available (database polling fallback).
    | heartbeat        Seconds between keep-alive comments sent to the client.
    | redis_block      Seconds the loop blocks waiting on a Redis wake signal.
    |
    */

    'stream' => [
        'ttl' => (int) env('MESSENGER_STREAM_TTL', 30),
        'poll_interval' => (int) env('MESSENGER_STREAM_POLL_MS', 1000),
        'heartbeat' => (int) env('MESSENGER_STREAM_HEARTBEAT', 15),
        'redis_block' => (int) env('MESSENGER_STREAM_REDIS_BLOCK', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Behaviour
    |--------------------------------------------------------------------------
    */

    // Minimum seconds between two "typing" events emitted by the same user in a chat.
    'typing_throttle' => (int) env('MESSENGER_TYPING_THROTTLE', 2),

    // How long realtime events are retained before pruning (hours).
    'event_retention_hours' => (int) env('MESSENGER_EVENT_RETENTION_HOURS', 48),

    // Max message length.
    'max_message_length' => (int) env('MESSENGER_MAX_MESSAGE_LENGTH', 5000),

    // Messages page size.
    'messages_per_page' => (int) env('MESSENGER_MESSAGES_PER_PAGE', 40),
];
