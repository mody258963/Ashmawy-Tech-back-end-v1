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
        if ($items->isEmpty()) {
            return IotSensorDataResource::collection($this->sensorData->paginateForDevice($model, 50));
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 50)));
        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $items->count();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return IotSensorDataResource::collection(
            new LengthAwarePaginator($slice->all(), $total, $perPage, $page, ['path' => $request->url()]),
        );
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

        $rows = $this->sensorData->latestPerType($model, 50);
        if ($rows->isNotEmpty()) {
            $meta = ['source' => 'database'];
            if (! config('iot.persist_sensor_readings_to_database', false)) {
                $meta['note'] = 'Redis had no snapshot; showing last rows from database (enable IOT_PERSIST_SENSOR_READINGS_TO_DB or fix Redis/MQTT for live data).';
            }

            return response()->json([
                'data' => IotSensorDataResource::collection($rows),
                'meta' => $meta,
            ]);
        }

        return response()->json([
            'data' => [],
            'meta' => [
                'source' => 'none',
                'hint' => 'Use GET /v1/iot/devices — the path id is iot_devices.id (may differ from MQTT iot_user_id). Then GET .../latest for current temperature.',
            ],
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
