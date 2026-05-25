<?php

namespace App\Http\Controllers\Iot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Iot\IotWebSensorSlotStoreRequest;
use App\Repository\Iot\IotDeviceRepository;
use App\Repository\Iot\IotSensorDataRepository;
use App\Repository\Iot\IotSensorSlotRepository;
use App\Services\Iot\IotRealtimeStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SensorSlotController extends Controller
{
    public function __construct(
        private readonly IotDeviceRepository $devices,
        private readonly IotSensorSlotRepository $slots,
        private readonly IotSensorDataRepository $sensorData,
        private readonly IotRealtimeStore $realtime,
    ) {}

    public function store(IotWebSensorSlotStoreRequest $request, int $device): RedirectResponse
    {
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $this->slots->createForDevice($model, $request->validated());

        return back()->with('status', __('Sensor added.'));
    }

    public function destroy(Request $request, int $device, int $slot): RedirectResponse
    {
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $row = $this->slots->findOnDeviceOrFail($slot, $model);
        $type = (string) $row->type;
        $this->slots->delete($row);
        $this->sensorData->deleteByType($model, $type);
        $this->realtime->forgetSensorLatest((int) $model->id, $type);

        return back()->with('status', __('Sensor removed.'));
    }
}
