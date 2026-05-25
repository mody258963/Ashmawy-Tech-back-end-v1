<?php

namespace App\Repository\Iot\Eloquent;

use App\Models\Iot\IotDevice;
use App\Models\Iot\IotSensorSlot;
use App\Repository\Iot\IotSensorSlotRepository;
use Illuminate\Support\Collection;

class IotSensorSlotEloquent implements IotSensorSlotRepository
{
    public function __construct(
        private readonly IotSensorSlot $model,
    ) {}

    public function forDevice(IotDevice $device): Collection
    {
        return $this->model->newQuery()
            ->where('iot_device_id', $device->id)
            ->orderBy('type')
            ->get();
    }

    public function findOnDeviceOrFail(int $slotId, IotDevice $device): IotSensorSlot
    {
        return $this->model->newQuery()
            ->where('iot_device_id', $device->id)
            ->whereKey($slotId)
            ->firstOrFail();
    }

    public function createForDevice(IotDevice $device, array $data): IotSensorSlot
    {
        return $this->model->newQuery()->create([
            'iot_device_id' => $device->id,
            'type' => $data['type'],
            'label' => $data['label'] ?? null,
            'is_critical' => (bool) ($data['is_critical'] ?? false),
        ]);
    }

    public function delete(IotSensorSlot $slot): void
    {
        $slot->delete();
    }
}
