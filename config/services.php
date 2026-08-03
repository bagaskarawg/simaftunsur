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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Klasterisasi K-Means (Python + FastAPI)
    |--------------------------------------------------------------------------
    |
    | Service Python berjalan terpisah (folder `ml/`, lihat ml/README.md).
    | Laravel memanggilnya via REST dari App\Services\KlasterisasiService.
    |
    */
    'ml' => [
        'base_url' => env('ML_BASE_URL', 'http://127.0.0.1:8001'),
        'timeout'  => (int) env('ML_TIMEOUT', 60),
        // Kunci API bersama (shared secret) untuk memanggil service klasterisasi
        // saat berjalan di server terpisah (mis. VPS). Kosongkan untuk dev lokal.
        'api_key'  => env('ML_API_KEY'),
    ],

];
