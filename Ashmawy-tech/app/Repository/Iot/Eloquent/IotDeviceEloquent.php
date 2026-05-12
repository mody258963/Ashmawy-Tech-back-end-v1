<?php

namespace App\Repository\Iot\Eloquent;

use App\Models\Iot\IotDevice;
use App\Models\Iot\IotUser;
use App\Repository\Iot\IotDeviceRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IotDeviceEloquent implements IotDeviceRepository
{
    public function __construct(
        private readonly IotDevice $model,
    ) {}

    public function paginateForUser(IotUser $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('iot_user_id', $user->id)
            ->withCount('components')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findOwnedOrFail(int $id, IotUser $user): IotDevice
    {
        return $this->model->newQuery()
            ->where('iot_user_id', $user->id)
            ->with(['components'])
            ->findOrFail($id);
    }

    public function findByUuidForUser(string $deviceUuid, int $iotUserId): ?IotDevice
    {
        return $this->model->newQuery()
            ->where('device_uuid', $deviceUuid)
            ->where('iot_user_id', $iotUserId)
            ->first();
    }

    public function iotUserIdForDeviceUuid(string $deviceUuid): ?int
    {
        $id = $this->model->newQuery()
            ->where('device_uuid', $deviceUuid)
            ->value('iot_user_id');

        return $id === null ? null : (int) $id;
    }
}
