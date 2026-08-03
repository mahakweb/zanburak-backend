<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Realtime acceleration with Redis
    |--------------------------------------------------------------------------
    |
    | The messenger is fully self-hosted and works with NO external service.
    | When MESSENGER_REDIS is enabled and Redis is reachable, the hot path uses
    | RAM for live delivery (Reverb broadcast + Redis outbox) and write-behind
    | flushes messages/events/receipts to Postgres in batches. Without Redis the
    | system falls back to durable DB writes while still broadcasting live with
    | ephemeral event ids so the UI never waits on /sync for open sockets.
    |
    */

    'redis' => [
        'enabled' => env('MESSENGER_REDIS', false),
        'connection' => env('MESSENGER_REDIS_CONNECTION', 'default'),
        'prefix' => env('MESSENGER_REDIS_PREFIX', 'messenger'),
    ],

    // Redis-first send/read/delivered when Redis is available.
    'hot_path' => (bool) env('MESSENGER_HOT_PATH', true),

    // Flush outbox when this many pending ops accumulate (also scheduled).
    'flush_batch_size' => (int) env('MESSENGER_FLUSH_BATCH', 20),

    // Seconds between scheduled outbox flushes.
    'flush_interval_seconds' => (int) env('MESSENGER_FLUSH_INTERVAL', 2),

    // How long pending outbox / hot-message keys live in Redis.
    'outbox_ttl' => (int) env('MESSENGER_OUTBOX_TTL', 86400),

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

    // Conversations page size (sidebar pagination / infinite scroll).
    'conversations_per_page' => (int) env('MESSENGER_CONVERSATIONS_PER_PAGE', 30),

    // Recent messages bundled with each conversation row on list/load-more.
    'recent_messages_in_list' => (int) env('MESSENGER_RECENT_MESSAGES_IN_LIST', 50),

    /*
    |--------------------------------------------------------------------------
    | Chat media (photo / video / voice / audio)
    |--------------------------------------------------------------------------
    |
    | Files are stored on the `static` disk (CDN). Limits are in kilobytes.
    |
    */
    'media' => [
        'disk' => env('MESSENGER_MEDIA_DISK', 'static'),
        // Dedicated CDN folder (alongside images/messenger/avatars|covers).
        'folder' => env('MESSENGER_MEDIA_FOLDER', 'images/messenger/chats'),
        // Defaults: 50 MB each (kilobytes). Server php.ini / nginx must allow >= this.
        'max_photo_kb' => (int) env('MESSENGER_MAX_PHOTO_KB', 51200),
        'max_video_kb' => (int) env('MESSENGER_MAX_VIDEO_KB', 51200),
        'max_audio_kb' => (int) env('MESSENGER_MAX_AUDIO_KB', 51200),
        'max_voice_kb' => (int) env('MESSENGER_MAX_VOICE_KB', 51200),
        'max_file_kb' => (int) env('MESSENGER_MAX_FILE_KB', 51200),
        'photo_mimes' => ['jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp'],
        'video_mimes' => ['mp4', 'webm', 'mov', 'm4v', '3gp'],
        'audio_mimes' => ['mp3', 'm4a', 'aac', 'ogg', 'wav', 'flac', 'opus'],
        'voice_mimes' => ['webm', 'ogg', 'mp4', 'm4a', 'aac', 'opus'],
        // Broad Telegram-like document set; blocked extensions are denied separately.
        'file_mimes' => [
            'zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz',
            'pdf', 'txt', 'md', 'rtf', 'csv', 'tsv', 'json', 'xml', 'yaml', 'yml', 'log',
            'svg', 'ico', 'psd', 'ai', 'eps', 'sketch',
            'doc', 'docx', 'odt', 'pages',
            'xls', 'xlsx', 'ods', 'numbers',
            'ppt', 'pptx', 'odp', 'key',
            'apk', 'aab', 'ipa',
            'ttf', 'otf', 'woff', 'woff2',
            'html', 'htm', 'css', 'scss', 'ts', 'tsx', 'jsx', 'vue', 'php', 'py', 'rb', 'go', 'rs', 'java', 'kt', 'swift', 'c', 'cpp', 'h', 'cs', 'sh',
            'sql', 'db', 'sqlite',
            'dmg', 'iso', 'img',
            'torrent',
            // "Send as file" may also upload media binaries as documents.
            'jpeg', 'jpg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif',
            'mp4', 'webm', 'mov', 'm4v', '3gp', 'mkv', 'avi',
            'mp3', 'm4a', 'aac', 'ogg', 'wav', 'flac', 'opus',
            'bin', 'dat', 'obj', 'fbx', 'gltf', 'glb',
        ],
        'file_blocked_mimes' => [
            'exe', 'bat', 'cmd', 'com', 'scr', 'msi', 'msp', 'pif',
            'vbs', 'vbe', 'wsf', 'wsh', 'ps1', 'psc1',
            'dll', 'sys', 'drv', 'cpl',
            'reg', 'inf', 'lnk', 'url',
            'jar', 'jnlp',
        ],
        'thumb_max_edge' => 48,
        'thumb_quality' => 45,
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint rate limits (requests per minute, except invite per hour)
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'baseline' => (int) env('MESSENGER_RATE_BASELINE', 180),
        'send' => (int) env('MESSENGER_RATE_SEND', 60),
        'search' => (int) env('MESSENGER_RATE_SEARCH', 30),
        'destructive' => (int) env('MESSENGER_RATE_DESTRUCTIVE', 30),
        'typing' => (int) env('MESSENGER_RATE_TYPING', 120),
        'sync' => (int) env('MESSENGER_RATE_SYNC', 120),
        'presence' => (int) env('MESSENGER_RATE_PRESENCE', 60),
        'invite' => (int) env('MESSENGER_RATE_INVITE', 5),
    ],
];
