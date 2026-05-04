<?php

namespace App\Http\Controllers\Api\V1\Iot;

use App\Http\Controllers\Controller;
use App\Http\Resources\Iot\IotSensorDataResource;
use App\Repository\Iot\IotDeviceRepository;
use App\Repository\Iot\IotSensorDataRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SensorController extends Controller
{
    public function __construct(
        private readonly IotDeviceRepository $devices,
        private readonly IotSensorDataRepository $sensorData,
    ) {}

    public function index(Request $request, int $device): AnonymousResourceCollection
    {
        $user = $request->user('iot-api');
        $model = $this->devices->findOwnedOrFail($device, $user);

        return IotSensorDataResource::collection($this->sensorData->paginateForDevice($model, 50));
    }

    public function latest(Request $request, int $device): JsonResponse
    {
        $user = $request->user('iot-api');
        $model = $this->devices->findOwnedOrFail($device, $user);
        $rows = $this->sensorData->latestPerType($model, 50);

        return response()->json([
            'data' => IotSensorDataResource::collection($rows),
        ]);
    }
}
