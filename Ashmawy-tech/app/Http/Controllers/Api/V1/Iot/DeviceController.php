<?php

namespace App\Http\Controllers\Api\V1\Iot;

use App\Http\Controllers\Controller;
use App\Http\Resources\Iot\IotDeviceResource;
use App\Repository\Iot\IotDeviceRepository;
use App\Services\Iot\DeviceJwtService;
use App\Services\Iot\IotRealtimeStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceController extends Controller
{
    public function __construct(
        private readonly IotDeviceRepository $devices,
        private readonly DeviceJwtService $deviceJwt,
        private readonly IotRealtimeStore $realtime,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user('iot-api');

        return IotDeviceResource::collection($this->devices->paginateForUser($user, 20));
    }

    public function show(Request $request, int $device): JsonResource
    {
        $user = $request->user('iot-api');
        $model = $this->devices->findOwnedOrFail($device, $user);

        return IotDeviceResource::make($model)->additional([
            'realtime' => $this->realtime->snapshotForDevice($model->id),
        ]);
    }

    public function regenerateJwt(Request $request, int $device): JsonResponse
    {
        $user = $request->user('iot-api');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $result = $this->deviceJwt->generate($model);

        return response()->json([
            'mqtt_jwt_token' => $result['token'],
            'jwt_expires_at' => $result['expires_at']->toIso8601String(),
        ]);
    }
}
