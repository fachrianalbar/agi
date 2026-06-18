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

    'total_kilat_gps' => [
        'base_url' => env('TOTAL_KILAT_GPS_BASE_URL', 'https://api.totalkilatgps.com'),
        'grant_type' => env('TOTAL_KILAT_GPS_GRANT_TYPE', 'totalkilatgps'),
        'connect_timeout' => (int) env('TOTAL_KILAT_GPS_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('TOTAL_KILAT_GPS_TIMEOUT', 20),
        'position_cache_seconds' => (int) env('TOTAL_KILAT_GPS_POSITION_CACHE_SECONDS', 60),
        'position_concurrency' => (int) env('TOTAL_KILAT_GPS_POSITION_CONCURRENCY', 5),
        'resolve_addresses_on_refresh' => (bool) env('TOTAL_KILAT_GPS_RESOLVE_ADDRESSES_ON_REFRESH', true),
    ],

    'map_tiles' => [
        'traffic_url' => env('MAP_TRAFFIC_TILE_URL'),
        'traffic_attribution' => env('MAP_TRAFFIC_TILE_ATTRIBUTION', ''),
    ],

];
