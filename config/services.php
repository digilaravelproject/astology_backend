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
    | TURN Server (for WebRTC NAT traversal)
    |--------------------------------------------------------------------------
    | Configure in .env to enable TURN relay for users behind restrictive NATs,
    | firewalls, or on 3G/4G mobile networks.
    |
    | Example (using Coturn or a cloud TURN provider):
    |   TURN_SERVER_URL=turn:your-turn.example.com:3478
    |   TURN_SERVER_USERNAME=your-username
    |   TURN_SERVER_CREDENTIAL=your-credential
    |
    | For production, always use a TURN server with credentials.
    | Google's free STUN only works for simple NAT scenarios.
    |--------------------------------------------------------------------------
    */
    'turn' => [
        'server_url' => env('TURN_SERVER_URL'),
        'username'   => env('TURN_SERVER_USERNAME'),
        'credential' => env('TURN_SERVER_CREDENTIAL'),
    ],

];
