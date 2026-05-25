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

    public function createForDevice(IotDevice $device, array $data): IotComponent
    {
        return $this->model->newQuery()->create([
            'iot_device_id' => $device->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'channel' => $data['channel'],
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function update(IotComponent $component, array $data): IotComponent
    {
        $component->fill([
            'name' => $data['name'] ?? $component->name,
            'type' => $data['type'] ?? $component->type,
            'channel' => $data['channel'] ?? $component->channel,
            'metadata' => array_key_exists('metadata', $data) ? $data['metadata'] : $component->metadata,
        ]);
        $component->save();

        return $component;
    }

    public function delete(IotComponent $component): void
    {
        $component->delete();
    }
}
