<?php

namespace App\Jobs\Iot;

use App\Models\Iot\IotSensorData;
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

class ProcessSensorDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $iotUserId,
        public string $deviceUuid,
        public string $sensorType,
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
        if (! $idempotency->claim($this->messageId)) {
            return;
        }

        $device = $devices->findByUuidForUser($this->deviceUuid, $this->iotUserId);
        if ($device === null) {
            Log::warning('IoT sensor message skipped: no device for topic user/uuid', [
                'iot_user_id' => $this->iotUserId,
                'device_uuid' => $this->deviceUuid,
                'sensor_type' => $this->sensorType,
            ]);

            return;
        }

        $decoded = json_decode($this->rawMessage, true);
        $value = is_array($decoded) ? $decoded : ['raw' => $this->rawMessage];

        try {
            $realtime->putSensorLatest((int) $device->id, $this->sensorType, $value, $this->messageId);
        } catch (Throwable $e) {
            Log::error('IoT Redis sensor write failed: '.$e->getMessage());
        }

        if (config('iot.persist_sensor_readings_to_database', false)) {
            IotSensorData::query()->create([
                'iot_device_id' => $device->id,
                'type' => $this->sensorType,
                'value' => $value,
                'message_id' => $this->messageId,
                'recorded_at' => now(),
            ]);
        }

        $automation->onSensorReading($device->id, $this->sensorType, $value);
    }
}
