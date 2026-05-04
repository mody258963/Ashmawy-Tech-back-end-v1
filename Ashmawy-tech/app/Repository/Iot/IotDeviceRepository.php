<?php

namespace App\Repository\Iot;

use App\Models\Iot\IotDevice;
use App\Models\Iot\IotUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IotDeviceRepository
{
    public function paginateForUser(IotUser $user, int $perPage = 15): LengthAwarePaginator;

    public function findOwnedOrFail(int $id, IotUser $user): IotDevice;

    public function findByUuidForUser(string $deviceUuid, int $iotUserId): ?IotDevice;
}
