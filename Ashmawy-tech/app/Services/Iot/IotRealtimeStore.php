<?php

namespace App\Services\Iot;

use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Latest telemetry / presence / module status for Flutter APIs (Redis).
 * MQTT QoS 0-style data is stored here; durable history stays optional (MySQL).
 */
class IotRealtimeStore
{
    private function prefix(): string
    {
        return rtrim((string) config('iot.redis_key_prefix', 'iot:v1'), ':');
    }

    private function sensorHashKey(int $iotDeviceId): string
    {
        return $this->prefix().':device:'.$iotDeviceId.':sensor_latest';
    }

    private function devicePresenceKey(int $iotDeviceId): string
    {
        return $this->prefix().':device:'.$iotDeviceId.':device_status';
    }

    private function moduleHashKey(int $iotDeviceId): string
    {
        return $this->prefix().':device:'.$iotDeviceId.':module_status';
    }

    public function putSensorLatest(int $iotDeviceId, string $sensorType, array $value, string $messageId): void
    {
        $ttl = (int) config('iot.sensor_latest_ttl_seconds', 86400 * 30);
        $payload = json_encode([
            'value' => $value,
            'recorded_at' => now()->toIso8601String(),
            'message_id' => $messageId,
        ], JSON_THROW_ON_ERROR);

        Redis::hSet($this->sensorHashKey($iotDeviceId), $sensorType, $payload);
        Redis::expire($this->sensorHashKey($iotDeviceId), max(60, $ttl));
    }

    /**
     * @return array<string, array{value: mixed, recorded_at: string, message_id: string}>
     */
    public function getSensorLatestAll(int $iotDeviceId): array
    {
        $raw = Redis::hGetAll($this->sensorHashKey($iotDeviceId));
        $out = [];
        foreach ($raw as $type => $json) {
            try {
                $decoded = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $out[(string) $type] = $decoded;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $out;
    }

    public function putDevicePresence(int $iotDeviceId, array $payload, string $normalizedStatus): void
    {
        $ttl = (int) config('iot.presence_ttl_seconds', 86400 * 30);
        $body = [
            'payload' => $payload,
            'status' => $normalizedStatus,
            'updated_at' => now()->toIso8601String(),
        ];
        Redis::set($this->devicePresenceKey($iotDeviceId), json_encode($body, JSON_THROW_ON_ERROR));
        Redis::expire($this->devicePresenceKey($iotDeviceId), max(60, $ttl));
    }

    /**
     * @return array{payload: array|string, status: string, updated_at: string}|null
     */
    public function getDevicePresence(int $iotDeviceId): ?array
    {
        $json = Redis::get($this->devicePresenceKey($iotDeviceId));
        if ($json === null || $json === false || $json === '') {
            return null;
        }
        try {
            $decoded = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function putModuleStatus(int $iotDeviceId, int $channel, array $payload, string $messageId): void
    {
        $ttl = (int) config('iot.module_status_ttl_seconds', 86400 * 30);
        $body = json_encode([
            'payload' => $payload,
            'recorded_at' => now()->toIso8601String(),
            'message_id' => $messageId,
        ], JSON_THROW_ON_ERROR);

        Redis::hSet($this->moduleHashKey($iotDeviceId), (string) $channel, $body);
        Redis::expire($this->moduleHashKey($iotDeviceId), max(60, $ttl));
    }

    /**
     * @return array<string, array{payload: mixed, recorded_at: string, message_id: string}>
     */
    public function getModuleStatuses(int $iotDeviceId): array
    {
        $raw = Redis::hGetAll($this->moduleHashKey($iotDeviceId));
        $out = [];
        foreach ($raw as $channel => $json) {
            try {
                $decoded = json_decode((string) $json, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $out[(string) $channel] = $decoded;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $out;
    }

    /**
     * @return array{sensors: array, device_presence: array|null, modules: array}
     */
    public function snapshotForDevice(int $iotDeviceId): array
    {
        return [
            'sensors' => $this->getSensorLatestAll($iotDeviceId),
            'device_presence' => $this->getDevicePresence($iotDeviceId),
            'modules' => $this->getModuleStatuses($iotDeviceId),
        ];
    }
}
