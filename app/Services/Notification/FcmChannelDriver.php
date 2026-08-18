<?php

namespace App\Services\Notification;

use App\Models\AdminFcmSetting;
use App\Models\UserDevice;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FcmChannelDriver
{
    protected ?AdminFcmSetting $setting = null;
    protected ?array $serviceAccount = null;

    public function __construct()
    {
        $this->setting = AdminFcmSetting::current();
    }

    /**
     * Check if FCM is fully configured and enabled.
     */
    public function isConfigured(): bool
    {
        if (!$this->setting || !$this->setting->is_active) {
            return false;
        }

        $filePath = $this->getServiceAccountPath();
        return !empty($filePath) && file_exists($filePath);
    }

    /**
     * Get the absolute path to the Firebase Service Account JSON file.
     */
    public function getServiceAccountPath(): ?string
    {
        if (!$this->setting || empty($this->setting->service_account_json_path)) {
            $defaultPath = storage_path('app/firebase/service-account.json');
            return file_exists($defaultPath) ? $defaultPath : null;
        }

        $path = $this->setting->service_account_json_path;
        if (file_exists($path)) {
            return $path;
        }

        $storagePath = storage_path($path);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        $appPath = storage_path('app/' . ltrim($path, '/\\'));
        if (file_exists($appPath)) {
            return $appPath;
        }

        return null;
    }

    /**
     * Load and parse service account credentials.
     */
    protected function getServiceAccount(): ?array
    {
        if ($this->serviceAccount !== null) {
            return $this->serviceAccount;
        }

        $path = $this->getServiceAccountPath();
        if (!$path || !file_exists($path)) {
            return null;
        }

        try {
            $content = file_get_contents($path);
            $json = json_decode($content, true);
            if (!is_array($json) || empty($json['private_key']) || empty($json['client_email'])) {
                Log::warning('FCM: Service account JSON is missing client_email or private_key.');
                return null;
            }
            $this->serviceAccount = $json;
            return $this->serviceAccount;
        } catch (Exception $e) {
            Log::error('FCM: Error reading service account file: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the Google Project ID from settings or JSON.
     */
    public function getProjectId(): ?string
    {
        if (!empty($this->setting?->project_id)) {
            return $this->setting->project_id;
        }

        $sa = $this->getServiceAccount();
        return $sa['project_id'] ?? null;
    }

    /**
     * Generate or retrieve cached Google OAuth2 Access Token.
     */
    public function getAccessToken(): ?string
    {
        $cacheKey = 'fcm_oauth2_access_token';
        
        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        $sa = $this->getServiceAccount();
        if (!$sa) {
            return null;
        }

        try {
            $now = time();
            $jwtPayload = [
                'iss'   => $sa['client_email'],
                'sub'   => $sa['client_email'],
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ];

            $jwt = JWT::encode($jwtPayload, $sa['private_key'], 'RS256');

            $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'] ?? null;
                $expiresIn = (int) ($data['expires_in'] ?? 3600);

                if ($accessToken) {
                    // Cache for slightly less than expiration (e.g. 50 minutes)
                    Cache::put($cacheKey, $accessToken, max(60, $expiresIn - 600));
                    return $accessToken;
                }
            }

            Log::error('FCM: Failed to obtain Google OAuth2 token: ' . $response->body());
            return null;
        } catch (Exception $e) {
            Log::error('FCM OAuth2 Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send push notification to a single FCM device token.
     *
     * @param string $deviceToken
     * @param PushNotificationPayload $payload
     * @return array ['success' => bool, 'message_id' => ?string, 'error' => ?string, 'unregistered' => bool]
     */
    public function sendToToken(string $deviceToken, PushNotificationPayload $payload): array
    {
        if (empty(trim($deviceToken))) {
            return ['success' => false, 'error' => 'Device token is empty', 'unregistered' => false];
        }

        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'FCM is not configured or disabled', 'unregistered' => false];
        }

        $projectId = $this->getProjectId();
        if (!$projectId) {
            return ['success' => false, 'error' => 'Firebase Project ID is missing', 'unregistered' => false];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'Unable to obtain Google OAuth2 token', 'unregistered' => false];
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $messageBody = $this->buildMessagePayload($deviceToken, $payload);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type'  => 'application/json; UTF-8',
            ])->timeout(10)->post($url, ['message' => $messageBody]);

            if ($response->successful()) {
                $resData = $response->json();
                return [
                    'success'    => true,
                    'message_id' => $resData['name'] ?? null,
                    'error'      => null,
                    'unregistered' => false,
                ];
            }

            $statusCode = $response->status();
            $errorJson = $response->json();
            $errorCode = $errorJson['error']['details'][0]['errorCode'] ?? $errorJson['error']['status'] ?? 'UNKNOWN_ERROR';
            $errorMessage = $errorJson['error']['message'] ?? $response->body();

            // Detect dead or invalid tokens for automatic cleanup
            $isUnregistered = in_array($errorCode, [
                'UNREGISTERED',
                'NOT_FOUND',
                'INVALID_ARGUMENT',
            ]) || str_contains($errorMessage, 'registration-token-not-registered')
               || str_contains($errorMessage, 'Requested entity was not found');

            if ($isUnregistered) {
                $this->handleDeadToken($deviceToken);
            }

            Log::warning("FCM Send Failed [{$statusCode} - {$errorCode}]: {$errorMessage}");

            return [
                'success'      => false,
                'message_id'   => null,
                'error'        => "{$errorCode}: {$errorMessage}",
                'unregistered' => $isUnregistered,
            ];

        } catch (Exception $e) {
            Log::error('FCM Send Exception: ' . $e->getMessage());
            return [
                'success'      => false,
                'message_id'   => null,
                'error'        => $e->getMessage(),
                'unregistered' => false,
            ];
        }
    }

    /**
     * Send push notification to multiple device tokens in batch.
     */
    public function sendToTokens(array $deviceTokens, PushNotificationPayload $payload): array
    {
        $results = [
            'total'      => count($deviceTokens),
            'successful' => 0,
            'failed'     => 0,
            'details'    => [],
        ];

        foreach (array_unique(array_filter($deviceTokens)) as $token) {
            $res = $this->sendToToken($token, $payload);
            $results['details'][$token] = $res;
            if ($res['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Build HTTP v1 Message Payload structure.
     */
    protected function buildMessagePayload(string $deviceToken, PushNotificationPayload $payload): array
    {
        $channelId = match ($payload->type) {
            'call' => $this->setting?->call_channel_id ?? 'call_channel',
            'chat' => $this->setting?->chat_channel_id ?? 'chat_channel',
            default => $this->setting?->default_channel_id ?? 'astology_notifications',
        };

        $sound = !empty($payload->sound) ? $payload->sound : ($this->setting?->default_sound ?? 'default');

        // All string key-values for data block
        $dataMap = [];
        foreach ($payload->customData as $k => $v) {
            $dataMap[(string) $k] = is_array($v) ? json_encode($v) : (string) $v;
        }

        // Add standard keys to data map
        $dataMap['type'] = (string) $payload->type;
        $dataMap['click_action'] = (string) $payload->clickAction;
        if ($payload->referenceId) {
            $dataMap['reference_id'] = (string) $payload->referenceId;
        }
        $dataMap['title'] = (string) $payload->title;
        $dataMap['body'] = (string) $payload->body;
        if ($payload->imageUrl) {
            $dataMap['image'] = (string) $payload->imageUrl;
        }

        $message = [
            'token' => $deviceToken,
            'data'  => $dataMap,
        ];

        // If NOT data-only, attach standard notification block for OS system trays
        if (!$payload->isDataOnly) {
            $message['notification'] = [
                'title' => $payload->title,
                'body'  => $payload->body,
            ];
            if ($payload->imageUrl) {
                $message['notification']['image'] = $payload->imageUrl;
            }
        }

        // Android configuration
        $message['android'] = [
            'priority' => ($payload->priority === 'high' || $payload->type === 'call') ? 'HIGH' : 'HIGH',
            'ttl'      => $payload->type === 'call' ? '60s' : '86400s',
        ];

        if (!$payload->isDataOnly) {
            $androidNotif = [
                'notification_priority'   => 'PRIORITY_HIGH',
                'default_vibrate_timings' => true,
                'visibility'              => 'PUBLIC',
                'click_action'            => $payload->clickAction,
            ];

            if ($channelId) {
                $androidNotif['channel_id'] = $channelId;
            }

            if ($sound === 'default') {
                $androidNotif['default_sound'] = true;
            } else {
                $androidNotif['sound'] = $sound;
            }

            if ($payload->imageUrl) {
                $androidNotif['image'] = $payload->imageUrl;
            }

            $message['android']['notification'] = $androidNotif;
        }

        // Apple APNs configuration
        $message['apns'] = [
            'headers' => [
                'apns-priority' => ($payload->priority === 'high' || $payload->type === 'call') ? '10' : '10',
            ],
            'payload' => [
                'aps' => [
                    'sound'             => $sound === 'default' ? 'default' : "{$sound}.caf",
                    'content-available' => 1,
                    'badge'             => 1,
                ],
            ],
        ];

        return $message;
    }

    /**
     * Automatically mark dead token as inactive in database.
     */
    protected function handleDeadToken(string $token): void
    {
        try {
            UserDevice::where('fcm_token', $token)->update(['is_active' => false]);
            Log::info("FCM: Marked invalid/unregistered token as inactive: " . substr($token, 0, 15) . "...");
        } catch (Exception $e) {
            Log::error("FCM: Failed to deactivate token: " . $e->getMessage());
        }
    }

    /**
     * Test connection against Firebase & Google APIs.
     */
    public function testConnection(): array
    {
        $path = $this->getServiceAccountPath();
        if (!$path || !file_exists($path)) {
            return [
                'success' => false,
                'message' => 'Service account JSON file not found on server.',
                'details' => ['path' => $path],
            ];
        }

        $sa = $this->getServiceAccount();
        if (!$sa) {
            return [
                'success' => false,
                'message' => 'Invalid or unreadable Service Account JSON file.',
                'details' => ['path' => $path],
            ];
        }

        $start = microtime(true);
        $token = $this->getAccessToken();
        $duration = round((microtime(true) - $start) * 1000, 2);

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Authentication failed. Could not exchange JWT for Google OAuth2 token.',
                'details' => [
                    'client_email' => $sa['client_email'] ?? 'unknown',
                    'project_id'   => $sa['project_id'] ?? 'unknown',
                    'duration_ms'  => $duration,
                ],
            ];
        }

        return [
            'success' => true,
            'message' => 'Successfully authenticated with Google OAuth2 & Firebase HTTP v1 API!',
            'details' => [
                'project_id'   => $this->getProjectId(),
                'client_email' => $sa['client_email'] ?? null,
                'duration_ms'  => $duration,
                'file_path'    => $path,
            ],
        ];
    }
}
