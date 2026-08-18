<?php

return [
    'worker_base_url' => env('WORKER_BASE_URL', 'https://worker.zanburak.ir'),
    'token_ttl_minutes' => env('UPLOAD_TOKEN_TTL', 20),
    'signing_key' => env('UPLOAD_TOKEN_SECRET', env('APP_KEY')),
    // Course video raw uploads (local media). Encode outputs use worker VIDEO_DISK / storage_disk.
    'video_disk' => env('VIDEO_DISK', 'media'),
];
