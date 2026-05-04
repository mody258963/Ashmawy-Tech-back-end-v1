<?php

namespace App\Jobs\Iot;

use App\Repository\Iot\IotDeviceRepository;
use App\Services\Iot\AutomationEngineStub;
use App\Services\Iot\IotMessageIdempotency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        AutomationEngineStub $automation,
    ): void {
        if (! $idempotency->claim($this->messageId)) {
            return;
        }

        $device = $devices->findByUuidForUser($this->deviceUuid, $this->iotUserId);
        if ($device === null) {
            return;
        }

        $payload = json_decode($this->rawMessage, true);
        $status = is_array($payload) && isset($payload['status']) ? (string) $payload['status'] : 'online';
        if (! in_array($status, ['online', 'offline'], true)) {
            $status = 'online';
        }

        $device->forceFill([
            'status' => $status,
            'last_seen' => now(),
        ])->save();

        $device->iotEvents()->create([
            'type' => 'device_status',
            'payload' => is_array($payload) ? $payload : ['raw' => $this->rawMessage],
            'message_id' => $this->messageId,
            'created_at' => now(),
        ]);

        $automation->onDeviceEvent($device->id, 'device_status', is_array($payload) ? $payload : null);
    }
}
