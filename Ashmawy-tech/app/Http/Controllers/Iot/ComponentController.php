<?php

namespace App\Http\Controllers\Iot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Iot\IotWebComponentStoreRequest;
use App\Http\Requests\Iot\IotWebComponentUpdateRequest;
use App\Repository\Iot\IotComponentRepository;
use App\Repository\Iot\IotDeviceRepository;
use App\Services\Iot\IotRealtimeStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    public function __construct(
        private readonly IotDeviceRepository $devices,
        private readonly IotComponentRepository $components,
        private readonly IotRealtimeStore $realtime,
    ) {}

    public function store(IotWebComponentStoreRequest $request, int $device): RedirectResponse
    {
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $this->components->createForDevice($model, $request->validated());

        return back()->with('status', __('Switch / component added.'));
    }

    public function update(IotWebComponentUpdateRequest $request, int $device, int $component): RedirectResponse
    {
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $comp = $this->components->findOnDeviceOrFail($component, $model);
        $oldChannel = (int) $comp->channel;
        $this->components->update($comp, $request->validated());
        if ((int) $comp->channel !== $oldChannel) {
            $this->realtime->forgetModuleStatus((int) $model->id, $oldChannel);
        }

        return back()->with('status', __('Component updated.'));
    }

    public function destroy(Request $request, int $device, int $component): RedirectResponse
    {
        $user = $request->user('iot-web');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $comp = $this->components->findOnDeviceOrFail($component, $model);
        $channel = (int) $comp->channel;
        $this->components->delete($comp);
        $this->realtime->forgetModuleStatus((int) $model->id, $channel);

        return back()->with('status', __('Component deleted.'));
    }
}
