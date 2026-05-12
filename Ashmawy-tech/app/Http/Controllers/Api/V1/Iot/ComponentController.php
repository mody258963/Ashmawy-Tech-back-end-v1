<?php

namespace App\Http\Controllers\Api\V1\Iot;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Iot\IotComponentActionRequest;
use App\Http\Requests\Api\V1\Iot\IotComponentStoreRequest;
use App\Http\Resources\Iot\IotComponentResource;
use App\Repository\Iot\IotComponentRepository;
use App\Repository\Iot\IotDeviceRepository;
use App\Services\Iot\ComponentControlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ComponentController extends Controller
{
    public function __construct(
        private readonly IotDeviceRepository $devices,
        private readonly IotComponentRepository $components,
        private readonly ComponentControlService $control,
    ) {}

    public function index(Request $request, int $device): AnonymousResourceCollection
    {
        $user = $request->user('iot-api');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $list = $this->components->forDevice($model);

        return IotComponentResource::collection($list);
    }

    public function store(IotComponentStoreRequest $request, int $device): JsonResponse
    {
        $user = $request->user('iot-api');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $comp = $this->components->createForDevice($model, $request->validated());

        return IotComponentResource::make($comp)->response()->setStatusCode(201);
    }

    public function action(IotComponentActionRequest $request, int $device, int $component): JsonResponse
    {
        $user = $request->user('iot-api');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $comp = $this->components->findOnDeviceOrFail($component, $model);

        $waitForAck = $request->boolean('wait_for_ack', true);
        $waitMs = $waitForAck
            ? min(
                max((int) $request->input('wait_ack_timeout_ms', (int) config('iot.mqtt_action_ack.wait_timeout_ms', 8000)), 0),
                30000,
            )
            : 0;

        $result = $this->control->execute(
            $user,
            $model,
            $comp,
            $request->validated('action'),
            $request->validated('value'),
            $waitMs,
        );

        return response()->json([
            'message' => 'ok',
            'mqtt_message_id' => $result['mqtt_message_id'],
            'ack_received' => $result['ack_received'],
            'ack_timed_out' => $result['ack_timed_out'],
            'device_applied_command' => $result['device_applied_command'],
            'command_ack_failed' => $result['command_ack_failed'],
            'device_status' => $result['device_status'],
            'status_recorded_at' => $result['status_recorded_at'],
        ]);
    }
}
