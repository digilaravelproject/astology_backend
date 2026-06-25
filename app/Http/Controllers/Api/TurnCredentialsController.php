<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TurnCredentialService;
use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class TurnCredentialsController extends Controller
{
    public function __construct(
        protected TurnCredentialService $turnService
    ) {}

    public function index(): JsonResponse
    {
        $iceServers = $this->turnService->getIceServers();

        $ttl = (int) config('services.turn.ttl', 86400);

        return ApiResponse::success([
            'iceServers' => $iceServers,
            'ttl'        => $ttl,
        ], 'ICE server configuration retrieved');
    }
}
