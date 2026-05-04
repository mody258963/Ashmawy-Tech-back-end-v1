<?php

namespace App\Console\Commands;

use App\Jobs\Iot\ProcessComponentSetJob;
use App\Jobs\Iot\ProcessDeviceStatusJob;
use App\Jobs\Iot\ProcessSensorDataJob;
use Illuminate\Console\Command;
use PhpMqtt\Client\ConnectionManager;
use PhpMqtt\Client\MqttClient;

class IotMqttSubscribe extends Command
{
    protected $signature = 'iot:mqtt-subscribe {--connection=default : MQTT connection name}';

    protected $description = 'Subscribe to IoT MQTT topics and dispatch ingestion jobs (long-running).';

    public function handle(ConnectionManager $manager): int
    {
        $connection = (string) $this->option('connection');
        $client = $manager->connection($connection);

        $qos = MqttClient::QOS_AT_LEAST_ONCE;

        $handler = function (string $topic, string $message, bool $retained, array $matchedWildcards): void {
            $this->routeMessage($topic, $message);
        };

        $client->subscribe('iot/+/+/component/+/set', $handler, $qos);
        $client->subscribe('iot/+/+/sensor/#', $handler, $qos);
        $client->subscribe('iot/+/+/device/status', $handler, $qos);

        $this->info('Subscribed to IoT topics; entering MQTT loop (Ctrl+C to exit).');

        $client->loop(true);

        return self::SUCCESS;
    }

    private function routeMessage(string $topic, string $message): void
    {
        $parts = explode('/', $topic);
        if (count($parts) < 5 || $parts[0] !== 'iot') {
            return;
        }

        $iotUserId = (int) $parts[1];
        $deviceUuid = $parts[2];
        $messageId = hash('sha256', $topic.'|'.$message);

        if ($parts[3] === 'component' && isset($parts[4], $parts[5]) && $parts[5] === 'set') {
            $channel = (int) $parts[4];
            ProcessComponentSetJob::dispatch($iotUserId, $deviceUuid, $channel, $message, $messageId);

            return;
        }

        if ($parts[3] === 'sensor') {
            $type = implode('/', array_slice($parts, 4));
            if ($type === '') {
                return;
            }
            ProcessSensorDataJob::dispatch($iotUserId, $deviceUuid, $type, $message, $messageId);

            return;
        }

        if ($parts[3] === 'device' && ($parts[4] ?? '') === 'status') {
            ProcessDeviceStatusJob::dispatch($iotUserId, $deviceUuid, $message, $messageId);
        }
    }
}
