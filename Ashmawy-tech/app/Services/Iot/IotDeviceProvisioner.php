<?php

namespace App\Services\Iot;

use App\Models\Iot\IotDevice;
use App\Models\Iot\IotUser;
use App\Repository\Iot\Eloquent\IotDeviceEloquent;
use App\Repository\Iot\IotDeviceRepository;
use Illuminate\Support\Str;

class IotDeviceProvisioner
{
    public function __construct(
        private readonly IotDeviceRepository $devices,
        private readonly DeviceJwtService $deviceJwt,
    ) {}

    /**
     * @param  array{name: string, location?: string|null, notes?: string|null}  $data
     */
    public function create(IotUser $user, array $data): IotDevice
    {
        $mqttUsername = $this->uniqueMqttUsername();
        $device = $this->devices->createForUser(
            $user,
            $data,
            (string) Str::uuid(),
            $mqttUsername,
        );
        $this->deviceJwt->generate($device);

        return $device->fresh();
    }

    private function uniqueMqttUsername(): string
    {
        do {
            $username = IotDeviceEloquent::generateMqttUsername();
        } while (\App\Models\Iot\IotDevice::query()->where('mqtt_username', $username)->exists());

        return $username;
    }
}
