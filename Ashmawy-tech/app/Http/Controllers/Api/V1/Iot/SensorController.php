<?php

namespace App\Http\Controllers\Api\V1\Iot;

use App\Http\Controllers\Controller;
use App\Http\Resources\Iot\IotSensorDataResource;
use App\Repository\Iot\IotDeviceRepository;
use App\Repository\Iot\IotSensorDataRepository;
use App\Services\Iot\IotRealtimeStore;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SensorController extends Controller
{
    public function __construct(
        private readonly IotDeviceRepository $devices,
        private readonly IotSensorDataRepository $sensorData,
        private readonly IotRealtimeStore $realtime,
    ) {}

    public function index(Request $request, int $device): AnonymousResourceCollection
    {
        $user = $request->user('iot-api');
        $model = $this->devices->findOwnedOrFail($device, $user);

        if (config('iot.persist_sensor_readings_to_database', false)) {
            return IotSensorDataResource::collection($this->sensorData->paginateForDevice($model, 50));
        }

        $items = $this->sensorSnapshotRows($model->id);
        $paginator = new LengthAwarePaginator(
            $items,
            $items->count(),
            max(1, $items->count()),
            1,
            ['path' => $request->url()],
        );

        return IotSensorDataResource::collection($paginator);
    }

    public function latest(Request $request, int $device): JsonResponse
    {
        $user = $request->user('iot-api');
        $model = $this->devices->findOwnedOrFail($device, $user);

        $redisRows = $this->sensorSnapshotRows($model->id);
        if ($redisRows->isNotEmpty()) {
            return response()->json([
                'data' => IotSensorDataResource::collection($redisRows),
                'meta' => ['source' => 'redis'],
            ]);
        }

        if (config('iot.persist_sensor_readings_to_database', false)) {
            $rows = $this->sensorData->latestPerType($model, 50);

            return response()->json([
                'data' => IotSensorDataResource::collection($rows),
                'meta' => ['source' => 'database'],
            ]);
        }

        return response()->json([
            'data' => [],
            'meta' => ['source' => 'none'],
        ]);
    }

    /**
     * @return Collection<int, object{id: null, type: string, value: mixed, recorded_at: Carbon}>
     */
    private function sensorSnapshotRows(int $iotDeviceId): Collection
    {
        $map = $this->realtime->getSensorLatestAll($iotDeviceId);

        return collect($map)->map(function (array $row, string $type): object {
            $recordedAt = isset($row['recorded_at'])
                ? Carbon::parse((string) $row['recorded_at'])
                : now();

            return (object) [
                'id' => null,
                'type' => $type,
                'value' => $row['value'] ?? null,
                'recorded_at' => $recordedAt,
            ];
        })->values();
    }
}
