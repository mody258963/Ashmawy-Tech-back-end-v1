<?php

namespace App\Http\Controllers\Api\V1\Iot;

use App\Http\Controllers\Controller;
use App\Http\Resources\Iot\IotDeviceResource;
use App\Repository\Iot\IotDeviceRepository;
use App\Services\Iot\DeviceJwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeviceController extends Controller
{
    public function __construct(
        private readonly IotDeviceRepository $devices,
        private readonly DeviceJwtService $deviceJwt,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user('iot-api');

        return IotDeviceResource::collection($this->devices->paginateForUser($user, 20));
    }

    public function show(Request $request, int $device): JsonResponse
    {
        $user = $request->user('iot-api');
        $model = $this->devices->findOwnedOrFail($device, $user);

        return response()->json(['data' => new IotDeviceResource($model)]);
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
