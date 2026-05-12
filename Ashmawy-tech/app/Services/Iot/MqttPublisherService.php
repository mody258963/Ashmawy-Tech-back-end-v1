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
    private const CONNECTION = 'default';

    public function __construct(
        private readonly ConnectionManager $mqtt,
    ) {}

    /**
     * @param  array<string, mixed>|null  $value
     */
    public function publishComponentCommand(IotDevice $device, IotComponent $component, string $action, ?array $value = null): void
    {
        $iotUserId = (int) $device->iot_user_id;
        $topic = IotTopic::componentSet($iotUserId, (string) $device->device_uuid, (int) $component->channel);

        $payload = [
            'action' => $action,
            'value' => $value,
            'message_id' => (string) Str::uuid(),
            'ts' => now()->toIso8601String(),
        ];

        /*
         * EMQX: one live session per client_id. php-fpm shares env MQTT_CLIENT_ID across workers → duplicate
         * connects cause broker to close sockets (EOF). Use a unique id per publish and disconnect so the
         * next request gets a clean session (see IotMqttSubscribe for long-lived subscriber id).
         */
        $base = (string) env('MQTT_CLIENT_ID', 'laravel-iot-backend');
        $clientIdKey = 'mqtt-client.connections.'.self::CONNECTION.'.client_id';
        Config::set($clientIdKey, $base.'-pub-'.getmypid().'-'.Str::lower(Str::random(8)));

        try {
            $client = $this->mqtt->connection(self::CONNECTION);
            // QoS 1 — user / API initiated actions must be delivered at least once to the device.
            $client->publish($topic, json_encode($payload), MqttClient::QOS_AT_LEAST_ONCE, false);
        } finally {
            try {
                $this->mqtt->disconnect(self::CONNECTION);
            } catch (\Throwable) {
                // Broker may already have closed the socket.
            }
        }
    }
}
