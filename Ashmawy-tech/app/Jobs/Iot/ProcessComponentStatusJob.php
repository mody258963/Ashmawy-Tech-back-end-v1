<?php

namespace App\Jobs\Iot;

use App\Repository\Iot\IotDeviceRepository;
use App\Services\Iot\AutomationEngineStub;
use App\Services\Iot\IotMessageIdempotency;
use App\Services\Iot\IotRealtimeStore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Device-published actuator/module status (MQTT .../component/{ch}/status) → Redis snapshot only.
 */
class ProcessComponentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $iotUserId,
        public string $deviceUuid,
        public int $channel,
        public string $rawMessage,
        public string $messageId,
    ) {
        $this->onQueue(config('iot.queue', 'iot'));
    }

    public function handle(
        IotDeviceRepository $devices,
        IotMessageIdempotency $idempotency,
        IotRealtimeStore $realtime,
        AutomationEngineStub $automation,
    ): void {
        $device = $devices->findByUuidForUser($this->deviceUuid, $this->iotUserId);
        if ($device === null) {
            Log::warning('IoT component status skipped: no device for topic user/uuid', [
                'iot_user_id' => $this->iotUserId,
                'device_uuid' => $this->deviceUuid,
                'channel' => $this->channel,
            ]);

            return;
        }

        if (! $idempotency->claim($this->messageId)) {
            return;
        }

        $decoded = json_decode($this->rawMessage, true);
        $payload = is_array($decoded) ? $decoded : ['raw' => $this->rawMessage];

        try {
            $realtime->putModuleStatus((int) $device->id, $this->channel, $payload, $this->messageId);
        } catch (Throwable $e) {
            Log::error('IoT Redis module status write failed: '.$e->getMessage());
        }

        $automation->onDeviceEvent($device->id, 'component_status', $payload);
    }
}
