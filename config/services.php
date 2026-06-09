<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'zarinpal' => [
        'token' => env('ZARINPAL_TOKEN'),
    ],

    'ghasedak' =>[
        'key' => env('GHASEDAK_API_KEY'),
        'line_number' => env('GHASEDAK_LINE_NUMBER', '10008566'),
    ],

    'meliPayamak' => [
        'username' => env('MELI_PAYAMAK_USERNAME', '19168474970'),
        'password' => env('MELI_PAYAMAK_PASSWORD', 'EQ9FG'),
        'notification_template_id' => env('MELI_PAYAMAK_NOTIFICATION_TEMPLATE_ID', null), // ID الگوی اطلاع‌رسانی در پنل MeliPayamak
        // OTP template should include Web OTP suffix, e.g. @zanburak.ir #{1} (see MeliPayamakChannel)
        'otp_template_id' => env('MELI_PAYAMAK_OTP_TEMPLATE_ID', '372965'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_SECRET_KEY'),
        'redirect' => env('GOOGLE_CALLBACK_URL'),
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_CALLBACK_URL'),
    ],

    'yahoo' => [
        'client_id' => env('YAHOO_CLIENT_ID'),
        'client_secret' => env('YAHOO_CLIENT_SECRET'),
        'redirect' => env('YAHOO_CALLBACK_URL'),
    ],

];
