<?php

namespace App\Services\Iot;

use App\Jobs\Iot\SendIotAlertPushJob;
use App\Models\Iot\IotDevice;
use App\Repository\Iot\IotSensorSlotRepository;
use Illuminate\Support\Facades\Log;

final class IotCriticalAlertService
{
    public function __construct(
        private readonly IotAppSession $appSession,
        private readonly IotSensorSlotRepository $sensorSlots,
    ) {}

    /**
     * Queue FCM when a critical sensor changes and the app is not in foreground for this device.
     *
     * @param  array<string, mixed>  $value
     * @param  array<string, mixed>|null  $previousValue
     */
    public function maybeNotify(IotDevice $device, string $sensorType, array $value, ?array $previousValue = null): void
    {
        $baseContext = [
            'device_id' => (int) $device->id,
            'iot_user_id' => (int) $device->iot_user_id,
            'sensor_type' => $sensorType,
            'value' => $value,
        ];

        if (! $this->sensorSlots->isCriticalForDevice((int) $device->id, $sensorType)) {
            Log::info('critical_alert: skipped — sensor not critical for device', $baseContext);

            return;
        }

        if ($this->appSession->active((int) $device->id)) {
            Log::info('critical_alert: skipped — app session active (foreground)', $baseContext);

            return;
        }

        if (! $this->isAlertState($sensorType, $value)) {
            Log::info('critical_alert: skipped — not an alert state', $baseContext);

            return;
        }

        if ($previousValue !== null && $this->sameAlertState($sensorType, $value, $previousValue)) {
            Log::info('critical_alert: skipped — same alert state as previous', array_merge($baseContext, [
                'previous_value' => $previousValue,
            ]));

            return;
        }

        $title = $device->name;
        $body = $this->alertBody($sensorType, $value);

        Log::info('critical_alert: queued for FCM push', [
            'type' => 'critical_alert',
            'iot_user_id' => (int) $device->iot_user_id,
            'device_id' => (int) $device->id,
            'device_name' => $title,
            'sensor_type' => $sensorType,
            'body' => $body,
        ]);

        SendIotAlertPushJob::dispatch(
            (int) $device->iot_user_id,
            (int) $device->id,
            $title,
            $body,
            [
                'type' => 'critical_alert',
                'device_id' => (string) $device->id,
                'sensor_type' => $sensorType,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function isAlertState(string $sensorType, array $value): bool
    {
        if ($sensorType === 'door_status') {
            $v = strtolower((string) ($value['v'] ?? ''));

            return str_contains($v, 'open');
        }

        if ($sensorType === 'motion') {
            return filter_var($value['v'] ?? false, FILTER_VALIDATE_BOOL);
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $value
     * @param  array<string, mixed>  $previous
     */
    private function sameAlertState(string $sensorType, array $value, array $previous): bool
    {
        if ($sensorType === 'door_status') {
            return strtolower((string) ($value['v'] ?? '')) === strtolower((string) ($previous['v'] ?? ''));
        }

        if ($sensorType === 'motion') {
            return (bool) ($value['v'] ?? false) === (bool) ($previous['v'] ?? false);
        }

        return ($value['v'] ?? null) === ($previous['v'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function alertBody(string $sensorType, array $value): string
    {
        if ($sensorType === 'door_status') {
            return 'Door: '.(string) ($value['v'] ?? 'open');
        }

        if ($sensorType === 'motion') {
            return 'Motion detected';
        }

        return ucfirst(str_replace('_', ' ', $sensorType)).': '.json_encode($value['v'] ?? $value);
    }
}
