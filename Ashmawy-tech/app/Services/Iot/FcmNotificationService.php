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
        $source = $this->credentialsSourceLabel();
        $projectId = $this->resolveProjectId();
        $result = [
            'ok' => false,
            'error' => null,
            'project_id' => $projectId,
            'credentials_path' => $source,
            'credentials_readable' => $this->credentialsAvailable(),
            'client_email' => null,
            'oauth_http_status' => null,
            'oauth_response' => null,
        ];

        if (! $this->credentialsAvailable()) {
            $result['error'] = 'No FCM credentials: set FCM_CREDENTIALS_JSON in .env or place JSON at '.$this->resolveCredentialsPath();

            return $result;
        }

        try {
            $json = $this->loadServiceAccount();
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

        if (! $this->credentialsAvailable()) {
            return '';
        }

        try {
            $json = $this->loadServiceAccount();

            return (string) ($json['project_id'] ?? '');
        } catch (Throwable) {
            return '';
        }
    }

    private function credentialsAvailable(): bool
    {
        if ($this->inlineCredentialsRaw() !== '') {
            return true;
        }

        $path = $this->resolveCredentialsPath();

        return $path !== '' && is_readable($path);
    }

    private function credentialsSourceLabel(): string
    {
        if ($this->inlineCredentialsRaw() !== '') {
            return 'env:FCM_CREDENTIALS_JSON';
        }

        return $this->resolveCredentialsPath();
    }

    private function inlineCredentialsRaw(): string
    {
        return trim((string) config('iot.fcm.credentials_json', ''));
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
    private function loadServiceAccount(): array
    {
        $inline = $this->inlineCredentialsRaw();
        if ($inline !== '') {
            return $this->parseServiceAccountString($inline);
        }

        $path = $this->resolveCredentialsPath();
        if ($path === '' || ! is_readable($path)) {
            throw new \RuntimeException('Credentials file not readable at '.$path);
        }

        return $this->parseServiceAccountString((string) file_get_contents($path));
    }

    /**
     * @return array<string, mixed>
     */
    private function parseServiceAccountString(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            throw new \RuntimeException('Empty credentials');
        }

        if (! str_starts_with($raw, '{')) {
            $decoded = base64_decode($raw, true);
            if ($decoded !== false && $decoded !== '') {
                $raw = $decoded;
            }
        }

        $json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
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

        if (! $this->credentialsAvailable()) {
            Log::warning('FCM skipped: no credentials (file or FCM_CREDENTIALS_JSON)', [
                'configured_path' => config('iot.fcm.credentials_path'),
                'resolved_path' => $this->resolveCredentialsPath(),
                'has_inline_json' => $this->inlineCredentialsRaw() !== '',
            ]);

            return null;
        }

        try {
            $json = $this->loadServiceAccount();
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
                'credentials_source' => $this->credentialsSourceLabel(),
            ]);

            return null;
        }
    }
}
