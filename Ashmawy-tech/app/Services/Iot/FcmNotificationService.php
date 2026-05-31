<?php

namespace App\Services\Iot;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class FcmNotificationService
{
    private ?string $accessToken = null;

    private int $accessTokenExpiresAt = 0;

    /**
     * @param  list<string>  $deviceTokens
     */
    public function sendToTokens(array $deviceTokens, string $title, string $body, array $data = [], bool $highPriority = false): void
    {
        $deviceTokens = array_values(array_filter(array_unique($deviceTokens)));
        $isCriticalAlert = $this->isCriticalAlert($data);
        $logContext = $this->criticalAlertLogContext($data, $title, $body);

        if ($deviceTokens === []) {
            if ($isCriticalAlert) {
                Log::warning('FCM critical_alert: skipped — no push tokens registered', $logContext);
            }

            return;
        }

        $projectId = $this->resolveProjectId();
        if ($projectId === '') {
            if ($isCriticalAlert) {
                Log::warning('FCM critical_alert: skipped — FCM_PROJECT_ID not set', $logContext);
            } else {
                Log::debug('FCM skipped: FCM_PROJECT_ID not set');
            }

            return;
        }

        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            if ($isCriticalAlert) {
                Log::warning('FCM critical_alert: skipped — OAuth access token unavailable', array_merge(
                    $logContext,
                    ['fcm_diagnosis' => $this->diagnose()['error'] ?? 'unknown'],
                ));
            }

            return;
        }

        if ($isCriticalAlert) {
            Log::info('FCM critical_alert: sending', array_merge($logContext, [
                'token_count' => count($deviceTokens),
                'high_priority' => $highPriority,
                'project_id' => $projectId,
            ]));
        }

        $url = 'https://fcm.googleapis.com/v1/projects/'.$projectId.'/messages:send';

        foreach ($deviceTokens as $token) {
            try {
                $message = [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_map(static fn ($v) => (string) $v, $data),
                ];

                if ($highPriority) {
                    $message['android'] = ['priority' => 'HIGH'];
                    $message['apns'] = [
                        'headers' => ['apns-priority' => '10'],
                    ];
                }

                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post($url, ['message' => $message]);

                if ($response->successful()) {
                    if ($isCriticalAlert) {
                        Log::info('FCM critical_alert: sent', array_merge($logContext, [
                            'token_suffix' => $this->maskToken($token),
                            'fcm_message_name' => $response->json('name'),
                        ]));
                    }
                } else {
                    Log::warning($isCriticalAlert ? 'FCM critical_alert: send failed' : 'FCM send failed', array_merge(
                        $isCriticalAlert ? $logContext : [],
                        [
                            'token_suffix' => $this->maskToken($token),
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ],
                    ));
                }
            } catch (Throwable $e) {
                Log::warning(
                    $isCriticalAlert ? 'FCM critical_alert: send exception' : 'FCM send exception',
                    array_merge(
                        $isCriticalAlert ? $logContext : [],
                        [
                            'token_suffix' => $this->maskToken($token),
                            'error' => $e->getMessage(),
                        ],
                    ),
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isCriticalAlert(array $data): bool
    {
        return ($data['type'] ?? '') === 'critical_alert';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function criticalAlertLogContext(array $data, string $title, string $body): array
    {
        return [
            'type' => $data['type'] ?? null,
            'device_id' => $data['device_id'] ?? null,
            'sensor_type' => $data['sensor_type'] ?? null,
            'title' => $title,
            'body' => $body,
        ];
    }

    private function maskToken(string $token): string
    {
        $length = strlen($token);

        return $length <= 8 ? '***' : '...'.substr($token, -8);
    }

    /**
     * Run before sending pushes (e.g. iot:test-push --check-fcm).
     *
     * @return array{
     *     ok: bool,
     *     error: string|null,
     *     project_id: string,
     *     credentials_path: string,
     *     credentials_readable: bool,
     *     client_email: string|null,
     *     oauth_http_status: int|null,
     *     oauth_response: string|null
     * }
     */
    public function diagnose(): array
    {
        $path = $this->resolveCredentialsPath();
        $projectId = $this->resolveProjectId();
        $result = [
            'ok' => false,
            'error' => null,
            'project_id' => $projectId,
            'credentials_path' => $path,
            'credentials_readable' => $path !== '' && is_readable($path),
            'client_email' => null,
            'oauth_http_status' => null,
            'oauth_response' => null,
        ];

        if ($path === '' || ! is_readable($path)) {
            $result['error'] = 'Credentials file missing or not readable at '.$path;

            return $result;
        }

        try {
            $json = $this->loadServiceAccountJson($path);
        } catch (Throwable $e) {
            $result['error'] = 'Invalid credentials JSON: '.$e->getMessage();

            return $result;
        }

        $result['client_email'] = (string) ($json['client_email'] ?? '');
        if ($result['client_email'] === '' || ($json['private_key'] ?? '') === '') {
            $result['error'] = 'Credentials JSON missing client_email or private_key';

            return $result;
        }

        if ($projectId === '') {
            $result['error'] = 'FCM_PROJECT_ID not set and project_id missing in credentials JSON';

            return $result;
        }

        try {
            $oauth = $this->requestOAuthToken($json);
        } catch (Throwable $e) {
            $result['error'] = 'OAuth request failed: '.$e->getMessage();

            return $result;
        }

        $result['oauth_http_status'] = $oauth['status'];
        $result['oauth_response'] = $oauth['body'];

        if (! $oauth['ok']) {
            $result['error'] = 'Google OAuth rejected token (HTTP '.$oauth['status'].'): '.$oauth['body'];

            return $result;
        }

        $result['ok'] = true;

        return $result;
    }

    private function resolveProjectId(): string
    {
        $fromEnv = (string) config('iot.fcm.project_id', '');
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $path = $this->resolveCredentialsPath();
        if ($path === '' || ! is_readable($path)) {
            return '';
        }

        try {
            $json = $this->loadServiceAccountJson($path);

            return (string) ($json['project_id'] ?? '');
        } catch (Throwable) {
            return '';
        }
    }

    private function resolveCredentialsPath(): string
    {
        $path = (string) config('iot.fcm.credentials_path', '');
        if ($path === '') {
            return '';
        }
        if (! str_starts_with($path, '/') && ! preg_match('#^[A-Za-z]:\\\\#', $path)) {
            return base_path($path);
        }

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadServiceAccountJson(string $path): array
    {
        $json = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($json)) {
            throw new \RuntimeException('Expected JSON object');
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $serviceAccount
     * @return array{ok: bool, status: int, body: string}
     */
    private function requestOAuthToken(array $serviceAccount): array
    {
        $clientEmail = (string) ($serviceAccount['client_email'] ?? '');
        $privateKey = (string) ($serviceAccount['private_key'] ?? '');
        $now = time();

        $jwt = JWT::encode([
            'iss' => $clientEmail,
            'sub' => $clientEmail,
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ], $privateKey, 'RS256');

        $response = Http::timeout(20)
            ->asForm()
            ->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        return [
            'ok' => $response->successful() && $response->json('access_token') !== null,
            'status' => $response->status(),
            'body' => $response->body(),
        ];
    }

    private function accessToken(): ?string
    {
        if ($this->accessToken !== null && time() < $this->accessTokenExpiresAt - 60) {
            return $this->accessToken;
        }

        $path = $this->resolveCredentialsPath();
        if ($path === '' || ! is_readable($path)) {
            Log::warning('FCM skipped: credentials file not readable', [
                'configured_path' => config('iot.fcm.credentials_path'),
                'resolved_path' => $path,
            ]);

            return null;
        }

        try {
            $json = $this->loadServiceAccountJson($path);
            $clientEmail = (string) ($json['client_email'] ?? '');
            $privateKey = (string) ($json['private_key'] ?? '');
            if ($clientEmail === '' || $privateKey === '') {
                Log::warning('FCM skipped: credentials JSON missing client_email or private_key', [
                    'resolved_path' => $path,
                ]);

                return null;
            }

            $oauth = $this->requestOAuthToken($json);
            if (! $oauth['ok']) {
                Log::warning('FCM OAuth failed', [
                    'http_status' => $oauth['status'],
                    'body' => $oauth['body'],
                    'client_email' => $clientEmail,
                ]);

                return null;
            }

            $decoded = json_decode($oauth['body'], true);
            $this->accessToken = is_array($decoded) ? (string) ($decoded['access_token'] ?? '') : '';
            $this->accessTokenExpiresAt = time() + (int) (is_array($decoded) ? ($decoded['expires_in'] ?? 3600) : 3600);

            return $this->accessToken !== '' ? $this->accessToken : null;
        } catch (Throwable $e) {
            Log::warning('FCM OAuth exception', [
                'error' => $e->getMessage(),
                'resolved_path' => $path,
            ]);

            return null;
        }
    }
}
