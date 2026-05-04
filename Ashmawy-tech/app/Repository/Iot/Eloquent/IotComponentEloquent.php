<?php

namespace App\Repository\Iot\Eloquent;

use App\Models\Iot\IotComponent;
use App\Models\Iot\IotDevice;
use App\Repository\Iot\IotComponentRepository;
use Illuminate\Support\Collection;

class IotComponentEloquent implements IotComponentRepository
{
    public function __construct(
        private readonly IotComponent $model,
    ) {}

    public function forDevice(IotDevice $device): Collection
    {
        return $this->model->newQuery()
            ->where('iot_device_id', $device->id)
            ->orderBy('channel')
            ->get();
    }

    public function findOnDeviceOrFail(int $componentId, IotDevice $device): IotComponent
    {
        return $this->model->newQuery()
            ->where('iot_device_id', $device->id)
            ->whereKey($componentId)
            ->firstOrFail();
    }
}
