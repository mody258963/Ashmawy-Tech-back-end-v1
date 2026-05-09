<?php

namespace App\Services\Iot;

use Illuminate\Support\Facades\Redis;

/**
 * When demand-gated ingestion is enabled, the MQTT subscriber connects only while this Redis key exists.
 * Flutter (or any IoT API client) refreshes it via POST /api/v1/iot/ingestion/heartbeat.
 */
final class IotSubscriberLease
{
    public function redisKey(): string
    {
        return (string) config('iot.subscriber_lease_redis_key', 'iot:ingestion:subscriber_lease');
    }

    public function touch(int $ttlSeconds): void
    {
        Redis::set($this->redisKey(), '1', 'EX', max(60, min(86400, $ttlSeconds)));
    }

    public function active(): bool
    {
        try {
            return (bool) Redis::exists($this->redisKey());
        } catch (\Throwable) {
            return false;
        }
    }
}
