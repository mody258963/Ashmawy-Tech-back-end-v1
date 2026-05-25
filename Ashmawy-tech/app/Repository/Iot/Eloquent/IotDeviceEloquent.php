<?php

namespace App\Repository\Iot\Eloquent;

use App\Models\Iot\IotDevice;
use App\Models\Iot\IotUser;
use App\Repository\Iot\IotDeviceRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class IotDeviceEloquent implements IotDeviceRepository
{
    public function __construct(
        private readonly IotDevice $model,
    ) {}

    public function paginateForUser(IotUser $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('iot_user_id', $user->id)
            ->withCount(['components', 'sensorSlots'])
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

    public function createForUser(IotUser $user, array $data, string $deviceUuid, string $mqttUsername): IotDevice
    {
        return $this->model->newQuery()->create([
            'iot_user_id' => $user->id,
            'device_uuid' => $deviceUuid,
            'name' => $data['name'],
            'location' => $data['location'] ?? null,
            'notes' => $data['notes'] ?? null,
            'mqtt_username' => $mqttUsername,
            'status' => 'offline',
        ]);
    }

    public function update(IotDevice $device, array $data): IotDevice
    {
        $device->fill([
            'name' => $data['name'] ?? $device->name,
            'location' => array_key_exists('location', $data) ? $data['location'] : $device->location,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $device->notes,
        ]);
        $device->save();

        return $device;
    }

    public function delete(IotDevice $device): void
    {
        $device->delete();
    }

    public static function generateMqttUsername(): string
    {
        return 'dev-'.Str::lower(Str::random(8));
    }
}
