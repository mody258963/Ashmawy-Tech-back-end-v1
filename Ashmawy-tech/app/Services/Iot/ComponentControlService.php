<?php

namespace App\Services\Iot;

use App\Models\Iot\IotComponent;
use App\Models\Iot\IotDevice;
use App\Models\Iot\IotUser;
use Illuminate\Support\Facades\DB;

class ComponentControlService
{
    public function __construct(
        private readonly MqttPublisherService $mqttPublisher,
    ) {}

    /**
     * @param  array<string, mixed>|null  $value
     */
    public function execute(IotUser $user, IotDevice $device, IotComponent $component, string $action, ?array $value = null): void
    {
        if ((int) $device->iot_user_id !== (int) $user->id) {
            abort(403, 'Device does not belong to this account.');
        }

        if ((int) $component->iot_device_id !== (int) $device->id) {
            abort(404, 'Component not found on this device.');
        }

        $allowed = ['ON', 'OFF', 'TOGGLE', 'SET'];
        if (! in_array($action, $allowed, true)) {
            abort(422, 'Invalid action.');
        }

        $storedValue = $value === null ? null : (is_array($value) ? $value : ['v' => $value]);

        DB::transaction(function () use ($device, $component, $action, $storedValue, $user): void {
            $device->actions()->create([
                'iot_component_id' => $component->id,
                'action' => $action,
                'value' => $storedValue,
                'triggered_by' => 'user',
                'triggered_by_id' => $user->id,
                'message_id' => null,
                'created_at' => now(),
            ]);

            $this->mqttPublisher->publishComponentCommand($device, $component, $action, $storedValue);
        });
    }
}
