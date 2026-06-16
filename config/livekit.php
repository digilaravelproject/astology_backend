<?php

return [
    'server_url' => env('LIVEKIT_SERVER_URL', 'http://localhost:7880'),
    'ws_url' => env('LIVEKIT_WS_URL', 'ws://localhost:7881'),
    'api_key' => env('LIVEKIT_API_KEY', 'devkey'),
    'api_secret' => env('LIVEKIT_API_SECRET', ''),
];
