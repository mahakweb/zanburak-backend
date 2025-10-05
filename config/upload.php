<?php

return [
    'worker_base_url' => env('WORKER_BASE_URL', 'https://worker.zanburak.ir'),
    'token_ttl_minutes' => env('UPLOAD_TOKEN_TTL', 20),
    'signing_key' => env('UPLOAD_TOKEN_SECRET', env('APP_KEY')),
];


