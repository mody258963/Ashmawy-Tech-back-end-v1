<?php

namespace App\Repository\Iot;

use App\Models\Iot\IotComponent;
use App\Models\Iot\IotDevice;
use Illuminate\Support\Collection;

interface IotComponentRepository
{
    /**
     * @return Collection<int, IotComponent>
     */
    public function forDevice(IotDevice $device): Collection;

    public function findOnDeviceOrFail(int $componentId, IotDevice $device): IotComponent;
}
