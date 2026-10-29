<?php

namespace App\Services;

use App\Models\Driver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send FCM Push Notification (HTTP v1 API)
     *
     * @param string $title
     * @param string $body
     * @param array|string $tokens
     * @param mixed $task
     * @param string $action
     * @param string|null $type notification key from resources/lang/{ar,en}/push.php
     * @return array FCM response per token
     */
    public function sendNotification($title, $body, $tokens, $task, $action, $type = null)
    {
        $tokens = array_filter(is_array($tokens) ? $tokens : [$tokens]);
        if (empty($tokens)) {
            return [];
        }

        $credentials = $this->credentials();
        if (!$credentials) {
            Log::warning('FCM: Firebase credentials are missing or invalid (FIREBASE_CREDENTIALS / FIREBASE_CREDENTIALS_BASE64), notification skipped.');
            return [];
        }

        $data = [
            "title" => $title,
            "body" => $body,
            "action" => $action,
        ];

        if ($type) {
            $data["notification_type"] = $type;
        }

        if ($task) {
            $from_location = $task->from ?? null;
            $to_location = $task->to ?? null;

            if ($from_location && $to_location) {
                $task->from_location_name = $from_location->name;
                $task->to_location_name = $to_location->name;

                $data["task_id"] = $task->id;
                $data["from_location_name"] = $from_location->name;
                $data["to_location_name"] = $to_location->name;
                $data["task_type"] = 'task';
                $data["task_object"] = json_encode($task);
            }
        }

        // HTTP v1 only accepts string values inside "data"
        $data = array_map(fn ($value) => (string) $value, $data);

        $url = 'https://fcm.googleapis.com/v1/projects/' . $credentials['project_id'] . '/messages:send';
        $results = [];

        try {
            $accessToken = $this->accessToken($credentials);

            // HTTP v1 sends to one token per request
            foreach ($tokens as $token) {
                $response = Http::withToken($accessToken)->post($url, [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => $data,
                        'android' => ['priority' => 'high'],
                        'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
                    ],
                ]);

                $results[$token] = $response->json();

                if ($response->failed()) {
                    $errorCode = collect($response->json('error.details', []))->pluck('errorCode')->filter()->first();

                    Log::error('FCM send failed', [
                        'project_id' => $credentials['project_id'],
                        'error_code' => $errorCode,
                        'hint' => $errorCode === 'SENDER_ID_MISMATCH'
                            ? 'Token was issued by an app linked to another Firebase project; the app config and FIREBASE_CREDENTIALS must be the same project'
                            : null,
                        'token' => $token,
                        'response' => $response->json(),
                    ]);

                    // The app was uninstalled or the token expired, stop sending to it
                    if ($errorCode === 'UNREGISTERED') {
                        Driver::where('fcm_token', $token)->update(['fcm_token' => null]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Never let a push failure break the calling flow (task creation, commands...)
            Log::error('FCM request failed: ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Load the Firebase service account JSON from a base64 env value or a file path
     */
    private function credentials()
    {
        $json = null;

        if ($base64 = config('services.fcm.credentials_base64')) {
            $json = base64_decode($base64);
        } elseif ($path = config('services.fcm.credentials')) {
            $path = str_starts_with($path, '/') ? $path : base_path($path);
            $json = is_file($path) ? file_get_contents($path) : null;
        }

        $credentials = $json ? json_decode($json, true) : null;

        return isset($credentials['project_id'], $credentials['client_email'], $credentials['private_key'])
            ? $credentials
            : null;
    }

    /**
     * Exchange a signed service account JWT for an OAuth2 access token (valid 1 hour)
     */
    private function accessToken(array $credentials)
    {
        return Cache::remember('fcm_access_token_' . md5($credentials['client_email']), now()->addMinutes(50), function () use ($credentials) {
            $now = time();
            $jwt = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])) . '.' . $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            if (!openssl_sign($jwt, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new \RuntimeException('Unable to sign FCM JWT, check the service account private_key');
            }

            $token = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt . '.' . $this->base64Url($signature),
            ])->throw()->json('access_token');

            if (!$token) {
                throw new \RuntimeException('Google OAuth did not return an access token');
            }

            return $token;
        });
    }

    private function base64Url($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
