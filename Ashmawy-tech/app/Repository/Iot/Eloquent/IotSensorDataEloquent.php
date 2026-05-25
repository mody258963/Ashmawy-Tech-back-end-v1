<?php

namespace App\Repository\Iot\Eloquent;

use App\Models\Iot\IotDevice;
use App\Models\Iot\IotSensorData;
use App\Repository\Iot\IotSensorDataRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class IotSensorDataEloquent implements IotSensorDataRepository
{
    public function __construct(
        private readonly IotSensorData $model,
    ) {}

    public function paginateForDevice(IotDevice $device, int $perPage = 50): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('iot_device_id', $device->id)
            ->orderByDesc('recorded_at')
            ->paginate($perPage);
    }

    public function latestPerType(IotDevice $device, int $limitTypes = 20): Collection
    {
        return $this->model->newQuery()
            ->where('iot_device_id', $device->id)
            ->whereIn('id', function ($query) use ($device): void {
                $query->selectRaw('MAX(id)')
                    ->from('iot_sensor_data')
                    ->where('iot_device_id', $device->id)
                    ->groupBy('type');
            })
            ->orderBy('type')
            ->limit($limitTypes)
            ->get();
    }

    public function deleteByType(IotDevice $device, string $type): int
    {
        return $this->model->newQuery()
            ->where('iot_device_id', $device->id)
            ->where('type', $type)
            ->delete();
    }
}
