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
        $device = $devices->findByUuidForUser($this->deviceUuid, $this->iotUserId);
        if ($device === null) {
            $ownerId = $devices->iotUserIdForDeviceUuid($this->deviceUuid);
            Log::warning('IoT sensor message skipped: no device for topic user/uuid', [
                'iot_user_id' => $this->iotUserId,
                'device_uuid' => $this->deviceUuid,
                'sensor_type' => $this->sensorType,
                'device_uuid_registered_under_iot_user_id' => $ownerId,
                'hint' => $ownerId === null
                    ? 'No iot_devices row has this device_uuid; fix ESP topic or create the device.'
                    : ($ownerId !== $this->iotUserId
                        ? 'Topic uses wrong iot_user_id: set ESP IOT_USER_ID to '.$ownerId.'.'
                        : ''),
            ]);

            return;
        }

        $decoded = json_decode($this->rawMessage, true);
        $value = is_array($decoded) ? $decoded : ['raw' => $this->rawMessage];

        if (config('iot.sensor_idempotency', true)) {
            $dedupeKey = $this->sensorDedupeKey((int) $device->id, $value);
            if (! $idempotency->claim($dedupeKey)) {
                Log::debug('IoT sensor skipped (duplicate seq)', [
                    'device_id' => $device->id,
                    'sensor_type' => $this->sensorType,
                    'seq' => $value['seq'] ?? null,
                ]);

                return;
            }
        }

        try {
            $realtime->putSensorLatest((int) $device->id, $this->sensorType, $value, $this->messageId);
            Log::info('IoT sensor stored in Redis', [
                'device_id' => $device->id,
                'sensor_type' => $this->sensorType,
                'seq' => $value['seq'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::error('IoT Redis sensor write failed: '.$e->getMessage(), [
                'device_id' => $device->id,
                'sensor_type' => $this->sensorType,
            ]);
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

    /**
     * Per reading seq when present; otherwise fall back to MQTT message hash.
     */
    private function sensorDedupeKey(int $iotDeviceId, array $value): string
    {
        if (array_key_exists('seq', $value)) {
            return 'sensor:'.$iotDeviceId.':'.$this->sensorType.':'.(string) $value['seq'];
        }

        return $this->messageId;
    }
}
