<?php

namespace App\Console\Commands;

use App\Jobs\Iot\ProcessComponentSetJob;
use App\Jobs\Iot\ProcessComponentStatusJob;
use App\Jobs\Iot\ProcessDeviceStatusJob;
use App\Jobs\Iot\ProcessSensorDataJob;
use App\Services\Iot\IotSubscriberLease;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionManager;
use PhpMqtt\Client\Exceptions\DataTransferException;
use PhpMqtt\Client\MqttClient;

class IotMqttSubscribe extends Command
{
    protected $signature = 'iot:mqtt-subscribe {--connection=default : MQTT connection name}';

    protected $description = 'Subscribe to IoT MQTT topics and dispatch ingestion jobs (long-running).';

    public function handle(ConnectionManager $manager): int
    {
        $connection = (string) $this->option('connection');

        $qosTelemetry = MqttClient::QOS_AT_MOST_ONCE;
        $qosActions = MqttClient::QOS_AT_LEAST_ONCE;

        $handler = function (string $topic, string $message, bool $retained, array $matchedWildcards): void {
            $this->routeMessage($topic, $message);
        };

        while (true) {
            $this->waitForSubscriberLeaseIfNeeded();

            try {
                $client = $manager->connection($connection);

                // QoS 1: backend-issued commands to devices (matches outbound publishes).
                $client->subscribe('iot/+/+/component/+/set', $handler, $qosActions);
                // QoS 0: telemetry / presence / module reports (best-effort; snapshots live in Redis).
                $client->subscribe('iot/+/+/sensor/#', $handler, $qosTelemetry);
                $client->subscribe('iot/+/+/device/status', $handler, $qosTelemetry);
                $client->subscribe('iot/+/+/component/+/status', $handler, $qosTelemetry);

                $clientId = (string) config('mqtt-client.connections.'.$connection.'.client_id');
                $this->info('Subscribed to IoT topics; entering MQTT loop (Ctrl+C to exit). client_id='.$clientId);

                $loopStartedAt = microtime(true);
                while ($this->shouldContinueMqttLoop()) {
                    $client->loopOnce($loopStartedAt, true);
                }

                $manager->disconnect($connection);

                if ($this->subscriberDemandGated()) {
                    $this->warn('Subscriber lease inactive; disconnected. Waiting for next heartbeat.');

                    continue;
                }

                return self::SUCCESS;
            } catch (DataTransferException $e) {
                $manager->disconnect($connection);
                Log::warning('IoT MQTT subscriber socket closed by broker; will reconnect.', [
                    'connection' => $connection,
                    'client_id' => config('mqtt-client.connections.'.$connection.'.client_id'),
                    'error' => $e->getMessage(),
                ]);
                $this->warn('MQTT disconnected (broker closed socket). Reconnecting in 5s…');
                sleep(5);
            }
        }
    }

    private function subscriberDemandGated(): bool
    {
        return (bool) config('iot.subscriber_demand_gated', false);
    }

    private function waitForSubscriberLeaseIfNeeded(): void
    {
        if (! $this->subscriberDemandGated()) {
            return;
        }

        $lease = app(IotSubscriberLease::class);

        while (! $lease->active()) {
            $this->comment('Waiting for IoT ingestion heartbeat (POST /api/v1/iot/ingestion/heartbeat) …');
            sleep(5);
        }

        $this->info('Subscriber lease active; connecting to MQTT broker.');
    }

    private function shouldContinueMqttLoop(): bool
    {
        if (! $this->subscriberDemandGated()) {
            return true;
        }

        return app(IotSubscriberLease::class)->active();
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

        if ($parts[3] === 'component' && isset($parts[4], $parts[5]) && $parts[5] === 'status') {
            $channel = (int) $parts[4];
            ProcessComponentStatusJob::dispatch($iotUserId, $deviceUuid, $channel, $message, $messageId);

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
