<?php

namespace App\Http\Controllers\Api\V1\Iot;

use App\Http\Controllers\Controller;
use App\Services\Iot\IotSubscriberLease;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngestionController extends Controller
{
    public function __construct(
        private readonly IotSubscriberLease $subscriberLease,
    ) {}

    /**
     * Extend the MQTT ingestion lease so `iot:mqtt-subscribe` stays connected (when demand-gated).
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $default = (int) config('iot.subscriber_heartbeat_ttl_seconds_default', 900);
        $ttl = (int) $request->input('ttl_seconds', $default);
        $ttl = max(60, min(86400, $ttl));

        $this->subscriberLease->touch($ttl);

        return response()->json([
            'subscriber_lease_seconds' => $ttl,
            'subscriber_demand_gated' => (bool) config('iot.subscriber_demand_gated', false),
        ]);
    }

    public function leaseStatus(): JsonResponse
    {
        return response()->json([
            'lease_active' => $this->subscriberLease->active(),
            'subscriber_demand_gated' => (bool) config('iot.subscriber_demand_gated', false),
        ]);
    }
}
