<?php

namespace App\Services\Iot;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class IotMessageIdempotency
{
    /**
     * @return bool true if this message should be processed (first time), false if duplicate
     *
     * Call only after the message is routed to a valid device (and component when applicable).
     * Claiming before DB resolution would block retries when the topic was wrong initially.
     */
    public function claim(string $messageId): bool
    {
        if ($messageId === '') {
            return true;
        }

        $ttl = (int) config('iot.idempotency_ttl_seconds', 86400);
        $key = 'iot:idemp:'.$messageId;

        try {
            $result = Redis::set($key, '1', 'EX', $ttl, 'NX');

            return $result === true || $result === 'OK';
        } catch (Throwable $e) {
            Log::warning('IoT idempotency Redis unavailable: '.$e->getMessage());

            return true;
        }
    }
}
