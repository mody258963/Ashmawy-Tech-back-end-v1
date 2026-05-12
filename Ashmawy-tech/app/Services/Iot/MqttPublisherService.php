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
