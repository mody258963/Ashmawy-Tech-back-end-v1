<?php

namespace App\Jobs\Iot;

use App\Models\Iot\IotComponent;
use App\Repository\Iot\IotDeviceRepository;
use App\Services\Iot\AutomationEngineStub;
use App\Services\Iot\IotMessageIdempotency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessComponentSetJob implements ShouldQueue
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
        AutomationEngineStub $automation,
    ): void {
        $device = $devices->findByUuidForUser($this->deviceUuid, $this->iotUserId);
        if ($device === null) {
            $ownerId = $devices->iotUserIdForDeviceUuid($this->deviceUuid);
            Log::warning('IoT component set skipped: no device for topic user/uuid', [
                'iot_user_id' => $this->iotUserId,
                'device_uuid' => $this->deviceUuid,
                'channel' => $this->channel,
                'device_uuid_registered_under_iot_user_id' => $ownerId,
                'hint' => $ownerId === null
                    ? 'No iot_devices row has this device_uuid.'
                    : ($ownerId !== $this->iotUserId
                        ? 'Set ESP IOT_USER_ID to '.$ownerId.'.'
                        : ''),
            ]);

            return;
        }

        $component = IotComponent::query()
            ->where('iot_device_id', $device->id)
            ->where('channel', $this->channel)
            ->first();

        if ($component === null) {
            return;
        }

        if (! $idempotency->claim($this->messageId)) {
            return;
        }

        $decoded = json_decode($this->rawMessage, true);
        $action = is_array($decoded) && isset($decoded['action']) ? strtoupper((string) $decoded['action']) : 'SET';
        if (! in_array($action, ['ON', 'OFF', 'TOGGLE', 'SET'], true)) {
            $action = 'SET';
        }

        $value = is_array($decoded) && array_key_exists('value', $decoded)
            ? (is_array($decoded['value']) ? $decoded['value'] : ['v' => $decoded['value']])
            : (is_array($decoded) ? $decoded : ['raw' => $this->rawMessage]);

        $device->actions()->create([
            'iot_component_id' => $component->id,
            'action' => $action,
            'value' => $value,
            'triggered_by' => 'system',
            'triggered_by_id' => null,
            'message_id' => $this->messageId,
            'created_at' => now(),
        ]);

        $component->forceFill([
            'last_state' => is_array($decoded) ? $decoded : ['raw' => $this->rawMessage],
            'last_state_at' => now(),
        ])->save();

        $automation->onDeviceEvent($device->id, 'component_set', is_array($decoded) ? $decoded : null);
    }
}
