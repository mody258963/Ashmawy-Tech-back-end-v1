<?php

namespace App\Http\Controllers\Iot;

use App\Http\Controllers\Controller;
use App\Repository\Iot\IotComponentRepository;
use App\Repository\Iot\IotDeviceRepository;
use App\Repository\Iot\IotSensorDataRepository;
use App\Services\Iot\ComponentControlService;
use App\Services\Iot\DeviceJwtService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function __construct(
        private readonly IotDeviceRepository $devices,
        private readonly IotComponentRepository $components,
        private readonly IotSensorDataRepository $sensorData,
        private readonly DeviceJwtService $deviceJwt,
        private readonly ComponentControlService $control,
    ) {}

    public function index(Request $request): View
    {
        /** @var \App\Models\Iot\IotUser $user */
        $user = $request->user('iot-web');

        return view('iot.devices.index', [
            'devices' => $this->devices->paginateForUser($user, 20),
        ]);
    }

    public function show(Request $request, int $device): View
    {
        /** @var \App\Models\Iot\IotUser $user */
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $components = $this->components->forDevice($model);
        $latestSensors = $this->sensorData->latestPerType($model, 24);

        return view('iot.devices.show', [
            'device' => $model,
            'components' => $components,
            'latestSensors' => $latestSensors,
        ]);
    }

    public function action(Request $request, int $device, int $component): RedirectResponse
    {
        /** @var \App\Models\Iot\IotUser $user */
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $comp = $this->components->findOnDeviceOrFail($component, $model);

        $data = $request->validate([
            'action' => ['required', 'string', 'in:ON,OFF,TOGGLE,SET'],
            'value' => ['nullable', 'array'],
        ]);

        $this->control->execute($user, $model, $comp, $data['action'], $data['value'] ?? null);

        return back()->with('status', __('Command queued.'));
    }

    public function regenerateJwt(Request $request, int $device): RedirectResponse
    {
        /** @var \App\Models\Iot\IotUser $user */
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $this->deviceJwt->generate($model);

        return back()->with('status', __('Device MQTT JWT regenerated.'));
    }
}
