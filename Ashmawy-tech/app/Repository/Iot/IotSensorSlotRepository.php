<?php

namespace App\Repository\Iot;

use App\Models\Iot\IotDevice;
use App\Models\Iot\IotSensorSlot;
use Illuminate\Support\Collection;

interface IotSensorSlotRepository
{
    /**
     * @return Collection<int, IotSensorSlot>
     */
    public function forDevice(IotDevice $device): Collection;

    public function findOnDeviceOrFail(int $slotId, IotDevice $device): IotSensorSlot;

    /**
     * @param  array{type: string, label?: string|null, is_critical?: bool}  $data
     */
    public function createForDevice(IotDevice $device, array $data): IotSensorSlot;

    public function delete(IotSensorSlot $slot): void;
}
