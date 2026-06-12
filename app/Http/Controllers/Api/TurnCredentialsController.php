<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Helpers\ApiResponse;

class TurnCredentialsController extends Controller
{
    /**
     * Provide ICE server configuration for WebRTC peer connections.
     *
     * Returns:
     *  - Google's free STUN server (always, for basic NAT traversal)
     *  - TURN server credentials if configured in .env (for restrictive NATs / 3G/4G)
     *
     * TURN is recommended for production. Configure these env vars:
     *   TURN_SERVER_URL=turn:your-turn.example.com:3478
     *   TURN_SERVER_USERNAME=your-username
     *   TURN_SERVER_CREDENTIAL=your-credential
     */
    public function index(): JsonResponse
    {
        $iceServers = [
            [
                'urls' => 'stun:stun.l.google.com:19302',
            ],
        ];

        $turnUrl = config('services.turn.server_url');
        $turnUsername = config('services.turn.username');
        $turnCredential = config('services.turn.credential');

        if ($turnUrl && $turnUsername && $turnCredential) {
            $iceServers[] = [
                'urls'       => $turnUrl,
                'username'   => $turnUsername,
                'credential' => $turnCredential,
            ];
        }

        return ApiResponse::success([
            'iceServers' => $iceServers,
            'ttl'        => 86400,
        ], 'ICE server configuration retrieved');
    }
}
