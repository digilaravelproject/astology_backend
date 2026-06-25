<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class TurnCredentialService
{
    protected const int DEFAULT_TTL = 86400;

    protected const int CACHE_TTL = 60;

    public function getIceServers(): array
    {
        $cacheKey = 'turn_credentials';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return $this->buildIceServers();
        });
    }

    protected function buildIceServers(): array
    {
        $iceServers = [
            [
                'urls' => 'stun:stun.l.google.com:19302',
            ],
        ];

        $turnUrl = config('services.turn.server_url');

        if (!$turnUrl) {
            return $iceServers;
        }

        $ttl = (int) config('services.turn.ttl', self::DEFAULT_TTL);
        $secret = config('services.turn.secret');

        if ($secret) {
            $iceServers[] = $this->buildTimeLimitedTurnServer($turnUrl, $secret, $ttl);
        } else {
            $username = config('services.turn.username');
            $credential = config('services.turn.credential');

            if ($username && $credential) {
                $iceServers[] = [
                    'urls'       => $turnUrl,
                    'username'   => $username,
                    'credential' => $credential,
                ];
            }
        }

        return $iceServers;
    }

    protected function buildTimeLimitedTurnServer(string $url, string $secret, int $ttl): array
    {
        $expires = now()->addSeconds($ttl)->unix();

        $sessionId = str_replace(['+', '/', '='], '', base64_encode(random_bytes(12)));

        $username = "{$expires}:{$sessionId}";

        $credential = base64_encode(
            hash_hmac('sha1', $username, $secret, binary: true)
        );

        return [
            'urls'       => $url,
            'username'   => $username,
            'credential' => $credential,
        ];
    }
}
