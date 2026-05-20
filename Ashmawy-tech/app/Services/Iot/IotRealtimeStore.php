<?php

namespace App\Services\Iot;

use Illuminate\Support\Facades\Redis;
use Throwable;
use Illuminate\Support\Facades\Log;

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
                Log::info('==========================================IoT getSensorLatestAll', ['decoded' => $decoded]);
                if (is_array($decoded)) {
                    $out[(string) $type] = $decoded;
                }
            } catch (Throwable $e) {
                Log::info('==========================================IoT getSensorLatestAll', ['error' => $e->getMessage()]);
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
            Log::info('==========================================IoT getDevicePresence', ['decoded' => $decoded]);
            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $e) {
            Log::info('==========================================IoT getDevicePresence', ['error' => $e->getMessage()]);
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
                Log::info('==========================================IoT getModuleStatuses', ['decoded' => $decoded]);
                if (is_array($decoded)) {
                    $out[(string) $channel] = $decoded;
                }
            } catch (Throwable $e) {
                Log::info('==========================================IoT getModuleStatuses', ['error' => $e->getMessage()]);
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

    /**
     * Block until Redis module snapshot for this channel contains payload.message_id === $commandMessageId
     * (ESP echoes Laravel's UUID on .../component/{ch}/status). Returns null on timeout.
     *
     * @return array{payload: array<string, mixed>, recorded_at: string, redis_message_id: string}|null
     */
    public function waitForModuleAckByCommandMessageId(
        int $iotDeviceId,
        int $channel,
        string $commandMessageId,
        int $timeoutMs,
        int $pollSleepMicroseconds = 100_000,
    ): ?array {
        if ($timeoutMs <= 0) {
            return null;
        }

        $deadlineMs = (int) round(microtime(true) * 1000) + $timeoutMs;
        $channelKey = (string) $channel;

        while ((int) round(microtime(true) * 1000) < $deadlineMs) {
            $modules = $this->getModuleStatuses($iotDeviceId);
            if (! isset($modules[$channelKey])) {
                usleep(max(1000, $pollSleepMicroseconds));
                continue;
            }

            $row = $modules[$channelKey];
            $payload = $row['payload'] ?? null;
            if (! is_array($payload)) {
                usleep(max(1000, $pollSleepMicroseconds));
                continue;
            }

            $echo = $payload['message_id'] ?? null;
            if ($echo !== null && (string) $echo === $commandMessageId) {
                return [
                    'payload' => $payload,
                    'recorded_at' => isset($row['recorded_at']) ? (string) $row['recorded_at'] : '',
                    'redis_message_id' => isset($row['message_id']) ? (string) $row['message_id'] : '',
                ];
            }

            usleep(max(1000, $pollSleepMicroseconds));
        }

        return null;
    }
}
