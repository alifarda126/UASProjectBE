<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/') . '/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Supabase Storage - S3 Compatible
        |--------------------------------------------------------------------------
        */

        's3' => [
            'driver' => 's3',

            /*
            |--------------------------------------------------------------------------
            | Supabase S3 Credentials
            |--------------------------------------------------------------------------
            */

            'key' => env('SUPABASE_S3_KEY'),

            'secret' => env('SUPABASE_S3_SECRET'),

            /*
            |--------------------------------------------------------------------------
            | Supabase S3 Configuration
            |--------------------------------------------------------------------------
            */

            'region' => env('AWS_DEFAULT_REGION'),

            'bucket' => env('AWS_BUCKET'),

            'url' => env('AWS_URL'),

            'endpoint' => env('AWS_ENDPOINT'),

            'use_path_style_endpoint' => true,

            /*
            |--------------------------------------------------------------------------
            | Error Handling
            |--------------------------------------------------------------------------
            |
            | throw=true agar error asli dari S3/Supabase
            | dapat diketahui saat proses upload.
            |
            */

            'throw' => true,

            'report' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [

        public_path('storage') => storage_path('app/public'),

    ],

];
