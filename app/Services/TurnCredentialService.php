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
        $username = config('services.turn.username');
        $credential = config('services.turn.credential');

        $cleanUrl = preg_replace('/\?transport=.*$/', '', $turnUrl);

        // 1. Static User Auth Priority (matches server /etc/turnserver.conf user=livekit:livekit_secret_2024)
        if ($username && $credential && empty($secret)) {
            $iceServers[] = [
                'urls'       => $cleanUrl,
                'username'   => $username,
                'credential' => $credential,
            ];
            $iceServers[] = [
                'urls'       => $cleanUrl . '?transport=tcp',
                'username'   => $username,
                'credential' => $credential,
            ];
        } elseif ($secret) {
            // 2. Dynamic HMAC ephemeral auth (when use-auth-secret is configured in coturn)
            $iceServers[] = $this->buildTimeLimitedTurnServer($cleanUrl, $secret, $ttl);
            $iceServers[] = $this->buildTimeLimitedTurnServer($cleanUrl . '?transport=tcp', $secret, $ttl);
        } elseif ($username && $credential) {
            $iceServers[] = [
                'urls'       => $cleanUrl,
                'username'   => $username,
                'credential' => $credential,
            ];
            $iceServers[] = [
                'urls'       => $cleanUrl . '?transport=tcp',
                'username'   => $username,
                'credential' => $credential,
            ];
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
