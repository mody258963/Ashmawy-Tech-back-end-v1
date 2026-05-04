<?php

namespace App\Services\Iot;

use App\Models\Iot\IotDevice;
use App\Support\Iot\IotTopic;
use DateTimeImmutable;
use Firebase\JWT\JWT;

class DeviceJwtService
{
    /**
     * @return array{token: string, expires_at: \Carbon\CarbonInterface}
     */
    public function generate(IotDevice $device): array
    {
        $secret = (string) config('iot.jwt.secret');
        if (strlen($secret) < 32) {
            throw new \RuntimeException('IOT_JWT_SECRET must be at least 32 characters.');
        }

        $iotUserId = (int) $device->iot_user_id;
        $uuid = (string) $device->device_uuid;
        $ttl = (int) config('iot.jwt.ttl_seconds', 86400 * 30);
        $now = new DateTimeImmutable;

        $base = IotTopic::base($iotUserId, $uuid);
        $acl = [
            $base.'/#',
        ];

        $payload = [
            'iss' => (string) config('iot.jwt.issuer'),
            'sub' => $device->mqtt_username,
            'iat' => $now->getTimestamp(),
            'exp' => $now->getTimestamp() + $ttl,
            'iot_user_id' => $iotUserId,
            'device_uuid' => $uuid,
            'username' => $device->mqtt_username,
            'acl' => $acl,
        ];

        $algorithm = (string) config('iot.jwt.algorithm', 'HS256');
        $token = JWT::encode($payload, $secret, $algorithm);

        $expiresAt = now()->addSeconds($ttl);

        $device->forceFill([
            'mqtt_jwt_token' => $token,
            'jwt_expires_at' => $expiresAt,
        ])->save();

        return [
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }
}
