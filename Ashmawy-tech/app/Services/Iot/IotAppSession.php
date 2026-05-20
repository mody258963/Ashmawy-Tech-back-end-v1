<?php

namespace App\Services\Iot;

use Illuminate\Support\Facades\Redis;

/**
 * Tracks when the mobile app has an active foreground session for a device (app heartbeat).
 * Used to suppress FCM pushes while the user is already viewing the device.
 */
final class IotAppSession
{
    public function redisKey(int $iotDeviceId): string
    {
        $pattern = (string) config('iot.app_session_redis_key', 'iot:app_session:device:%d');

        return sprintf($pattern, $iotDeviceId);
    }

    public function touch(int $iotDeviceId, int $ttlSeconds): void
    {
        Redis::set($this->redisKey($iotDeviceId), '1', 'EX', max(60, min(86400, $ttlSeconds)));
    }

    public function clear(int $iotDeviceId): void
    {
        Redis::del($this->redisKey($iotDeviceId));
    }

    public function active(int $iotDeviceId): bool
    {
        try {
            return (bool) Redis::exists($this->redisKey($iotDeviceId));
        } catch (\Throwable) {
            return false;
        }
    }
}
