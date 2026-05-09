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

class ProcessDeviceStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $iotUserId,
        public string $deviceUuid,
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
            Log::warning('IoT device status skipped: no device for topic user/uuid', [
                'iot_user_id' => $this->iotUserId,
                'device_uuid' => $this->deviceUuid,
            ]);

            return;
        }

        if (! $idempotency->claim($this->messageId)) {
            return;
        }

        $payload = json_decode($this->rawMessage, true);
        $status = is_array($payload) && isset($payload['status']) ? (string) $payload['status'] : 'online';
        if (! in_array($status, ['online', 'offline'], true)) {
            $status = 'online';
        }

        $normalizedPayload = is_array($payload) ? $payload : ['raw' => $this->rawMessage];

        try {
            $realtime->putDevicePresence((int) $device->id, $normalizedPayload, $status);
        } catch (Throwable $e) {
            Log::error('IoT Redis device presence write failed: '.$e->getMessage());
        }

        $device->forceFill([
            'status' => $status,
            'last_seen' => now(),
        ])->save();

        $device->iotEvents()->create([
            'type' => 'device_status',
            'payload' => $normalizedPayload,
            'message_id' => $this->messageId,
            'created_at' => now(),
        ]);

        $automation->onDeviceEvent($device->id, 'device_status', is_array($payload) ? $payload : null);
    }
}
