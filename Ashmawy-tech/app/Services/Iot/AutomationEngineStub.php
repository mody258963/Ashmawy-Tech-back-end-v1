<?php

namespace App\Services\Iot;

/**
 * Placeholder for future automation rules (triggers, schedules, alerts).
 */
class AutomationEngineStub
{
    public function onSensorReading(int $deviceId, string $type, array $value): void
    {
        // Intentionally empty — wire rules engine here later.
    }

    public function onDeviceEvent(int $deviceId, string $type, ?array $payload): void
    {
        // Intentionally empty.
    }
}
