<?php

namespace App\Services\Iot;

use App\Models\Iot\IotComponent;
use App\Models\Iot\IotDevice;
use App\Models\Iot\IotDeviceAction;
use App\Models\Iot\IotUser;
use Illuminate\Support\Facades\DB;

class ComponentControlService
{
    public function __construct(
        private readonly MqttPublisherService $mqttPublisher,
        private readonly IotRealtimeStore $realtime,
    ) {}

    /**
     * @param  array<string, mixed>|null  $value
     * @param  ?int  $waitForAckTimeoutMs  null = use config `iot.mqtt_action_ack.wait_timeout_ms`; 0 = do not wait
     * @return array{
     *     mqtt_message_id: string,
     *     ack_received: bool,
     *     ack_timed_out: bool,
     *     device_applied_command: bool,
     *     command_ack_failed: bool,
     *     device_status: array<string, mixed>|null,
     *     status_recorded_at: string|null,
     *     ack_outcome: string,
     * }
     */
    public function execute(
        IotUser $user,
        IotDevice $device,
        IotComponent $component,
        string $action,
        ?array $value = null,
        ?int $waitForAckTimeoutMs = null,
    ): array {
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

        $timeoutMs = $waitForAckTimeoutMs ?? (int) config('iot.mqtt_action_ack.wait_timeout_ms', 8000);

        $mqttMessageId = '';
        $actionId = null;

        DB::transaction(function () use ($device, $component, $action, $storedValue, $user, &$mqttMessageId, &$actionId): void {
            $actionRecord = $device->actions()->create([
                'iot_component_id' => $component->id,
                'action' => $action,
                'value' => $storedValue,
                'triggered_by' => 'user',
                'triggered_by_id' => $user->id,
                'message_id' => null,
                'ack_outcome' => 'pending',
                'ack_payload' => null,
                'created_at' => now(),
            ]);
            $actionId = $actionRecord->id;

            $mqttMessageId = $this->mqttPublisher->publishComponentCommand($device, $component, $action, $storedValue);

            $actionRecord->forceFill(['message_id' => $mqttMessageId])->save();
        });

        $ack = null;
        if ($timeoutMs > 0) {
            $ack = $this->realtime->waitForModuleAckByCommandMessageId(
                (int) $device->id,
                (int) $component->channel,
                $mqttMessageId,
                $timeoutMs,
            );
        }

        $ackReceived = $ack !== null;

        $payload = $ackReceived ? $ack['payload'] : null;
        $deviceApplied = $ackReceived;
        $commandAckFailed = false;
        if ($ackReceived && is_array($payload) && array_key_exists('command_ack', $payload)) {
            $ok = filter_var($payload['command_ack'], FILTER_VALIDATE_BOOL);
            $commandAckFailed = ! $ok;
            $deviceApplied = $ok;
        }

        $ackOutcome = 'pending';
        if ($actionId !== null) {
            $ackOutcome = 'timeout';
            if ($timeoutMs <= 0) {
                $ackOutcome = 'no_wait';
            } elseif ($ackReceived) {
                $ackOutcome = $commandAckFailed ? 'nack' : 'acknowledged';
            }
            IotDeviceAction::query()->whereKey($actionId)->update([
                'ack_outcome' => $ackOutcome,
                'ack_payload' => is_array($payload) ? $payload : null,
            ]);
        }

        return [
            'mqtt_message_id' => $mqttMessageId,
            'ack_received' => $ackReceived,
            'ack_timed_out' => $timeoutMs > 0 && ! $ackReceived,
            'device_applied_command' => $deviceApplied,
            'command_ack_failed' => $commandAckFailed,
            'device_status' => $payload,
            'status_recorded_at' => $ackReceived ? ($ack['recorded_at'] !== '' ? $ack['recorded_at'] : null) : null,
            'ack_outcome' => $ackOutcome,
        ];
    }
}
