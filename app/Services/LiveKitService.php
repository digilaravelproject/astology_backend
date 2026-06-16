<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiveKitService
{
    private string $serverUrl;
    private string $wsUrl;
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $this->serverUrl = config('livekit.server_url');
        $this->wsUrl = config('livekit.ws_url');
        $this->apiKey = config('livekit.api_key');
        $this->apiSecret = config('livekit.api_secret');
    }

    public function getWsUrl(): string
    {
        return $this->wsUrl;
    }

    public function createRoom(string $roomName): array
    {
        try {
            $token = $this->generateApiToken();
            $response = Http::withToken($token, 'Bearer')
                ->timeout(5)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->serverUrl}/twirp/livekit.RoomService/CreateRoom", [
                    'name' => $roomName,
                    'empty_timeout' => 300,
                    'max_participants' => 0,
                ]);

            if ($response->successful()) {
                Log::debug('LiveKit room created', ['room' => $roomName]);
                return $response->json();
            }

            if ($response->status() === 409) {
                Log::debug('LiveKit room already exists', ['room' => $roomName]);
                return ['name' => $roomName];
            }

            Log::error('LiveKit createRoom failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('LiveKit room creation failed: ' . $response->body());
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('LiveKit createRoom exception', ['error' => $e->getMessage()]);
            throw new \RuntimeException('LiveKit service unavailable: ' . $e->getMessage());
        }
    }

    public function deleteRoom(string $roomName): void
    {
        try {
            $token = $this->generateApiToken();
            $response = Http::withToken($token, 'Bearer')
                ->timeout(5)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->serverUrl}/twirp/livekit.RoomService/DeleteRoom", [
                    'room' => $roomName,
                ]);

            if ($response->successful()) {
                Log::debug('LiveKit room deleted', ['room' => $roomName]);
                return;
            }

            Log::warning('LiveKit deleteRoom failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('LiveKit deleteRoom exception', ['error' => $e->getMessage()]);
        }
    }

    private function generateApiToken(): string
    {
        $now = time();
        $payload = [
            'iss' => $this->apiKey,
            'exp' => $now + 300,
            'iat' => $now,
            'nbf' => $now,
            'video' => [
                'rest' => true,
            ],
        ];

        $header = $this->base64urlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payloadEncoded = $this->base64urlEncode(json_encode($payload));
        $signature = $this->base64urlEncode(
            hash_hmac('sha256', "{$header}.{$payloadEncoded}", $this->apiSecret, true)
        );

        return "{$header}.{$payloadEncoded}.{$signature}";
    }

    public function generateToken(
        string $roomName,
        string $identity,
        bool $canPublish = false,
        bool $canSubscribe = true,
        int $ttl = 3600
    ): string {
        $now = time();
        $payload = [
            'iss' => $this->apiKey,
            'sub' => $identity,
            'exp' => $now + $ttl,
            'iat' => $now,
            'nbf' => $now,
            'video' => [
                'room' => $roomName,
                'roomJoin' => true,
                'canPublish' => $canPublish,
                'canSubscribe' => $canSubscribe,
            ],
        ];

        $header = $this->base64urlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payloadEncoded = $this->base64urlEncode(json_encode($payload));
        $signature = $this->base64urlEncode(
            hash_hmac('sha256', "{$header}.{$payloadEncoded}", $this->apiSecret, true)
        );

        return "{$header}.{$payloadEncoded}.{$signature}";
    }

    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
