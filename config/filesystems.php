<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [


        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],


        'static' => [
            'driver' => 'ftp',
            'host' => env('STATIC_FTP_HOST'),
            'username' => env('STATIC_FTP_USERNAME'),
            'password' => env('STATIC_FTP_PASSWORD'),
            'url' => env('STATIC_URL', 'https://static.zanburak.ir'),
            'root' => env('STATIC_FTP_ROOT', '/public_html'),
            'passive' => true,
        ],

        /*
         * Course videos (raw + HLS + downloads). Shared local folder so worker
         * and backend both read/write without the 100MB static FTP quota.
         */
        'media' => [
            'driver' => 'local',
            'root' => env('MEDIA_ROOT', 'D:/zanburak-media'),
            'throw' => true,
        ],

        'dl' => [
            'driver' => 'ftp',
            'host' => env('DL_FTP_HOST'),
            'username' => env('DL_FTP_USERNAME'),
            'password' => env('DL_FTP_PASSWORD'),
            'url' => env('DL_URL', 'https://dl.zanburak.ir'),
            'root' => env('DL_FTP_ROOT', '/public_html'),
            'passive' => true,
            'timeout' => (int) env('DL_FTP_TIMEOUT', 600),
        ],

        'secrets' => [
            'driver' => 'local',
            'root' => storage_path('app/secrets'),
        ],


        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
