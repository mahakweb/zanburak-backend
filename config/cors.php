<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'],

    /*
    | You cannot use '*' with credentials. Prefer explicit origins via env or patterns.
    | Example .env:
    | CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000,https://zanburak.ir,https://www.zanburak.ir
    */
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://127.0.0.1:3000,http://localhost:3001,http://127.0.0.1:3001')))),

    // Allow localhost on any port and any subdomain of zanburak.ir
    'allowed_origins_patterns' => [
        '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
        '#^https?://([a-z0-9-]+\.)*zanburak\.ir$#i',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Allow cookies (credentials) to be sent with cross-origin requests
    'supports_credentials' => true,

];
