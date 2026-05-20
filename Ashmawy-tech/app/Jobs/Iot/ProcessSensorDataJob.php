<?php

namespace App\Jobs\Iot;

use App\Models\Iot\IotSensorData;
use App\Repository\Iot\IotDeviceRepository;
use App\Services\Iot\AutomationEngineStub;
use App\Services\Iot\IotCriticalAlertService;
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
        IotRealtimeStore $realtime,
        AutomationEngineStub $automation,
        IotCriticalAlertService $criticalAlerts,
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

        $previousSnapshot = $realtime->getSensorLatestAll((int) $device->id);
        $previousValue = isset($previousSnapshot[$this->sensorType]['value'])
            && is_array($previousSnapshot[$this->sensorType]['value'])
            ? $previousSnapshot[$this->sensorType]['value']
            : null;

        try {
            $realtime->putSensorLatest((int) $device->id, $this->sensorType, $value, $this->messageId);
            $criticalAlerts->maybeNotify($device, $this->sensorType, $value, $previousValue);
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
}
