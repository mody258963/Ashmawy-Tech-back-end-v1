<?php

namespace App\Repository\Iot;

use App\Models\Iot\IotDevice;
use App\Models\Iot\IotSensorData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface IotSensorDataRepository
{
    public function paginateForDevice(IotDevice $device, int $perPage = 50): LengthAwarePaginator;

    /**
     * @return Collection<int, IotSensorData>
     */
    public function latestPerType(IotDevice $device, int $limitTypes = 20): Collection;
}
