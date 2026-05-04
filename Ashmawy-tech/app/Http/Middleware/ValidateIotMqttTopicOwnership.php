<?php

namespace App\Http\Middleware;

/**
 * HTTP middleware placeholder: MQTT topic ownership is enforced in ingestion jobs
 * ({@see \App\Jobs\Iot\ProcessSensorDataJob}) using iot_user_id + device_uuid from the topic path.
 *
 * Use this class if you later expose an internal webhook or bridge that accepts raw MQTT payloads over HTTP.
 */
class ValidateIotMqttTopicOwnership
{
    // Reserved for future HTTP/MQTT bridge validation.
}
