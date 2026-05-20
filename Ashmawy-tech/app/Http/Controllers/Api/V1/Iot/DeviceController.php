<?php

namespace App\Http\Controllers\Api\V1\Iot;

use App\Http\Controllers\Controller;
use App\Http\Resources\Iot\IotDeviceResource;
use App\Repository\Iot\IotDeviceRepository;
use App\Services\Iot\DeviceJwtService;
use App\Services\Iot\IotAppSession;
use App\Services\Iot\IotRealtimeStore;
use App\Services\Iot\IotSubscriberLease;
use App\Services\Iot\MqttPublisherService;
use App\Support\Iot\IotTopic;
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
        private readonly MqttPublisherService $mqttPublisher,
        private readonly IotAppSession $appSession,
        private readonly IotSubscriberLease $subscriberLease,
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

    /**
     * App foreground: wake ESP32 sensor streaming via MQTT; mark app session for push gating.
     */
    public function appHeartbeat(Request $request, int $device): JsonResponse
    {
        $user = $request->user('iot-api');
        $model = $this->devices->findOwnedOrFail($device, $user);

        $defaultTtl = (int) config('iot.app_heartbeat_ttl_default', 300);
        $ttl = (int) $request->input('ttl_seconds', $defaultTtl);
        $ttl = max(60, min(3600, $ttl));

        $streaming = $request->boolean('streaming', true);

        if ($streaming) {
            $this->appSession->touch((int) $model->id, $ttl);
        } else {
            $this->appSession->clear((int) $model->id);
        }

        $mqttMessageId = $this->mqttPublisher->publishAppHeartbeat($model, $ttl, $streaming);

        $this->subscriberLease->touch($ttl);

        return response()->json([
            'message' => 'ok',
            'mqtt_message_id' => $mqttMessageId,
            'mqtt_topic' => IotTopic::appHeartbeat((int) $model->iot_user_id, (string) $model->device_uuid),
            'streaming' => $streaming,
            'ttl_seconds' => $ttl,
            'app_session_active' => $streaming,
            'subscriber_lease_seconds' => $ttl,
            'subscriber_demand_gated' => (bool) config('iot.subscriber_demand_gated', false),
        ]);
    }
}
