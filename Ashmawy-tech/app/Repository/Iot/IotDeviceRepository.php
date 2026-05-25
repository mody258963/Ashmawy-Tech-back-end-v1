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

    /**
     * If any device row uses this UUID, return its `iot_users.id` owner (else null).
     * Used when MQTT topic user id does not match DB (firmware wrong IOT_USER_ID).
     */
    public function iotUserIdForDeviceUuid(string $deviceUuid): ?int;

    /**
     * @param  array{name: string, location?: string|null, notes?: string|null}  $data
     */
    public function createForUser(IotUser $user, array $data, string $deviceUuid, string $mqttUsername): IotDevice;

    /**
     * @param  array{name?: string, location?: string|null, notes?: string|null}  $data
     */
    public function update(IotDevice $device, array $data): IotDevice;

    public function delete(IotDevice $device): void;
}
