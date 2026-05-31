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

        $projectId = (string) config('iot.fcm.project_id', '');
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
                Log::warning('FCM critical_alert: skipped — OAuth access token unavailable', $logContext);
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

    private function accessToken(): ?string
    {
        if ($this->accessToken !== null && time() < $this->accessTokenExpiresAt - 60) {
            return $this->accessToken;
        }

        $path = (string) config('iot.fcm.credentials_path', '');
        if ($path === '' || ! is_readable($path)) {
            Log::debug('FCM skipped: credentials file not readable at '.$path);

            return null;
        }

        try {
            $json = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $clientEmail = (string) ($json['client_email'] ?? '');
            $privateKey = (string) ($json['private_key'] ?? '');
            if ($clientEmail === '' || $privateKey === '') {
                return null;
            }

            $now = time();
            $jwt = JWT::encode([
                'iss' => $clientEmail,
                'sub' => $clientEmail,
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ], $privateKey, 'RS256');

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                Log::warning('FCM OAuth failed', ['body' => $response->body()]);

                return null;
            }

            $this->accessToken = (string) $response->json('access_token');
            $this->accessTokenExpiresAt = $now + (int) $response->json('expires_in', 3600);

            return $this->accessToken !== '' ? $this->accessToken : null;
        } catch (Throwable $e) {
            Log::warning('FCM OAuth exception: '.$e->getMessage());

            return null;
        }
    }
}
