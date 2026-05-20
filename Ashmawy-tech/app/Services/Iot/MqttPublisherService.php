<?php

namespace App\Services\Iot;

use App\Models\Iot\IotComponent;
use App\Models\Iot\IotDevice;
use App\Support\Iot\IotTopic;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use PhpMqtt\Client\ConnectionManager;
use PhpMqtt\Client\MqttClient;

class MqttPublisherService
{
    private const CONNECTION = 'publisher';

    public function __construct(
        private readonly ConnectionManager $mqtt,
    ) {}

    /**
     * @param  array<string, mixed>|null  $value
     * @return string MQTT payload message_id (UUID) echoed by device on .../status for API correlation
     */
    public function publishComponentCommand(IotDevice $device, IotComponent $component, string $action, ?array $value = null): string
    {
        $iotUserId = (int) $device->iot_user_id;
        $topic = IotTopic::componentSet($iotUserId, (string) $device->device_uuid, (int) $component->channel);

        $messageId = (string) Str::uuid();
        $payload = [
            'action' => $action,
            'value' => $value,
            'message_id' => $messageId,
            'ts' => now()->toIso8601String(),
        ];

        $this->publishJson($topic, $payload);

        return $messageId;
    }

    /**
     * Tell the device (ESP32) to publish sensor telemetry for a limited time (app foreground).
     *
     * @return string MQTT payload message_id
     */
    public function publishAppHeartbeat(IotDevice $device, int $ttlSeconds, bool $streaming = true): string
    {
        $topic = IotTopic::appHeartbeat((int) $device->iot_user_id, (string) $device->device_uuid);

        $messageId = (string) Str::uuid();
        $payload = [
            'streaming' => $streaming,
            'ttl_seconds' => max(60, min(3600, $ttlSeconds)),
            'message_id' => $messageId,
            'ts' => now()->toIso8601String(),
        ];

        $this->publishJson($topic, $payload);

        return $messageId;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function publishJson(string $topic, array $payload): void
    {
        $clientIdKey = 'mqtt-client.connections.'.self::CONNECTION.'.client_id';
        $base = (string) config('mqtt-client.publisher_client_id_base', 'laravel-iot-backend-publisher');
        $hostSlug = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', (string) gethostname()));
        $hostSlug = trim($hostSlug, '-') ?: 'host';
        Config::set(
            $clientIdKey,
            $base.'-w-'.$hostSlug.'-'.getmypid().'-'.Str::lower(Str::random(6)),
        );

        try {
            $this->mqtt->disconnect(self::CONNECTION);
        } catch (\Throwable) {
            // No pooled connection yet.
        }

        try {
            $client = $this->mqtt->connection(self::CONNECTION);
            $client->publish($topic, json_encode($payload), MqttClient::QOS_AT_LEAST_ONCE, false);
            $client->loop(true, true, 5);
        } finally {
            try {
                $this->mqtt->disconnect(self::CONNECTION);
            } catch (\Throwable) {
                // Broker may already have closed the socket.
            }
        }
    }
}
