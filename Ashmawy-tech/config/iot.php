<?php

return [

    'jwt' => [
        'secret' => env('IOT_JWT_SECRET'),
        'algorithm' => env('IOT_JWT_ALGORITHM', 'HS256'),
        'ttl_seconds' => (int) env('IOT_JWT_TTL_SECONDS', 60 * 60 * 24 * 30),
        'issuer' => env('IOT_JWT_ISS', env('APP_URL', 'http://localhost')),
    ],

    'queue' => env('IOT_QUEUE', 'iot'),

    'idempotency_ttl_seconds' => (int) env('IOT_IDEMPOTENCY_TTL', 86400),

    /*
    | Latest QoS-0-style telemetry & module snapshots for Flutter (Redis).
    | MQTT subscriber uses QoS 0 for these topics; commands use QoS 1 from Laravel → EMQX.
    */
    'redis_key_prefix' => env('IOT_REDIS_KEY_PREFIX', 'iot:v1'),

    'sensor_latest_ttl_seconds' => (int) env('IOT_SENSOR_REDIS_TTL', 86400 * 30),

    'presence_ttl_seconds' => (int) env('IOT_PRESENCE_REDIS_TTL', 86400 * 30),

    'module_status_ttl_seconds' => (int) env('IOT_MODULE_STATUS_REDIS_TTL', 86400 * 30),

    /** When false, sensor readings are only mirrored to Redis (recommended with QoS 0 telemetry). */
    'persist_sensor_readings_to_database' => filter_var(
        env('IOT_PERSIST_SENSOR_READINGS_TO_DB', false),
        FILTER_VALIDATE_BOOL,
    ),

    /*
    | Run ProcessSensorDataJob inside iot:mqtt-subscribe (recommended).
    | If false, php artisan queue:work --queue=iot must run 24/7 or Redis never updates.
    */
    'sensor_process_inline' => filter_var(
        env('IOT_SENSOR_PROCESS_INLINE', true),
        FILTER_VALIDATE_BOOL,
    ),

    /*
    | Dedupe sensor MQTT by payload "seq" (per device + type). Avoids stale hash(topic|body) keys
    | blocking Redis updates after queue retries or broker redelivery.
    */
    'sensor_idempotency' => filter_var(env('IOT_SENSOR_IDEMPOTENCY', true), FILTER_VALIDATE_BOOL),

    /*
    | Demand-gated MQTT subscriber: connects only while Redis lease is refreshed (Flutter heartbeat).
    | Queue worker should still run continuously — it is idle when the queue is empty.
    */
    'subscriber_demand_gated' => filter_var(
        env('IOT_SUBSCRIBER_DEMAND_GATED', false),
        FILTER_VALIDATE_BOOL,
    ),

    'subscriber_lease_redis_key' => env('IOT_SUBSCRIBER_LEASE_REDIS_KEY', 'iot:ingestion:subscriber_lease'),

    'subscriber_heartbeat_ttl_seconds_default' => (int) env('IOT_SUBSCRIBER_HEARTBEAT_TTL_DEFAULT', 900),

    /*
    | POST .../components/{id}/action: poll Redis for ESP .../component/{ch}/status echoing the same
    | message_id as the outbound MQTT command (door-lock firmware publishes it). 0 = no wait.
    */
    'mqtt_action_ack' => [
        'wait_timeout_ms' => (int) env('IOT_ACTION_ACK_WAIT_MS', 8000),
    ],

];
