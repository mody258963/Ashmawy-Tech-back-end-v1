<?php

namespace App\Http\Controllers\Iot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Iot\IotWebDeviceStoreRequest;
use App\Http\Requests\Iot\IotWebDeviceUpdateRequest;
use App\Repository\Iot\IotComponentRepository;
use App\Repository\Iot\IotDeviceRepository;
use App\Repository\Iot\IotSensorDataRepository;
use App\Repository\Iot\IotSensorSlotRepository;
use App\Services\Iot\ComponentControlService;
use App\Services\Iot\DeviceJwtService;
use App\Services\Iot\IotDeviceProvisioner;
use App\Services\Iot\IotRealtimeStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function __construct(
        private readonly IotDeviceRepository $devices,
        private readonly IotComponentRepository $components,
        private readonly IotSensorSlotRepository $sensorSlots,
        private readonly IotSensorDataRepository $sensorData,
        private readonly IotDeviceProvisioner $provisioner,
        private readonly DeviceJwtService $deviceJwt,
        private readonly ComponentControlService $control,
        private readonly IotRealtimeStore $realtime,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user('iot-web');

        return view('iot.devices.index', [
            'devices' => $this->devices->paginateForUser($user, 20),
        ]);
    }

    public function create(): View
    {
        return view('iot.devices.create');
    }

    public function store(IotWebDeviceStoreRequest $request): RedirectResponse
    {
        $user = $request->user('iot-web');
        $device = $this->provisioner->create($user, $request->validated());

        return redirect()
            ->route('iot.devices.show', $device)
            ->with('status', __('Customer site created. Copy MQTT credentials into the ESP32 sketch.'));
    }

    public function show(Request $request, int $device): View
    {
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $components = $this->components->forDevice($model);
        $slots = $this->sensorSlots->forDevice($model);
        $liveSensors = $this->realtime->getSensorLatestAll((int) $model->id);
        $moduleStatuses = $this->realtime->getModuleStatuses((int) $model->id);
        $dbLatest = $this->sensorData->latestPerType($model, 50)->keyBy('type');
        $presence = $this->realtime->getDevicePresence((int) $model->id);

        return view('iot.devices.show', [
            'device' => $model,
            'components' => $components,
            'sensorSlots' => $slots,
            'liveSensors' => $liveSensors,
            'moduleStatuses' => $moduleStatuses,
            'dbLatest' => $dbLatest,
            'presence' => $presence,
        ]);
    }

    public function edit(Request $request, int $device): View
    {
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);

        return view('iot.devices.edit', ['device' => $model]);
    }

    public function update(IotWebDeviceUpdateRequest $request, int $device): RedirectResponse
    {
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $this->devices->update($model, $request->validated());

        return redirect()
            ->route('iot.devices.show', $model)
            ->with('status', __('Customer site updated.'));
    }

    public function destroy(Request $request, int $device): RedirectResponse
    {
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $this->devices->delete($model);

        return redirect()
            ->route('iot.devices.index')
            ->with('status', __('Customer site and all switches/sensors removed.'));
    }

    public function action(Request $request, int $device, int $component): RedirectResponse
    {
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $comp = $this->components->findOnDeviceOrFail($component, $model);

        $data = $request->validate([
            'action' => ['required', 'string', 'in:ON,OFF,TOGGLE,SET'],
            'value' => ['nullable', 'array'],
        ]);

        $this->control->execute($user, $model, $comp, $data['action'], $data['value'] ?? null, 0);

        return back()->with('status', __('Command queued.'));
    }

    public function regenerateJwt(Request $request, int $device): RedirectResponse
    {
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $this->deviceJwt->generate($model);

        return back()->with('status', __('Device MQTT JWT regenerated.'));
    }
}
