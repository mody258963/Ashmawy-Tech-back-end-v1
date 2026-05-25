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

    /**
     * @param  array{name: string, type: string, channel: int, metadata?: array|null}  $data
     */
    public function createForDevice(IotDevice $device, array $data): IotComponent;

    /**
     * @param  array{name?: string, type?: string, channel?: int, metadata?: array|null}  $data
     */
    public function update(IotComponent $component, array $data): IotComponent;

    public function delete(IotComponent $component): void;
}
