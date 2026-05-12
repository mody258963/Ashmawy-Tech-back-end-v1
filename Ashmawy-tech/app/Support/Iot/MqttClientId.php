<?php

namespace App\Support\Iot;

/**
 * EMQX allows one online MQTT session per client_id. Helpers keep logical base + unique suffixes.
 */
final class MqttClientId
{
    public static function logicalBase(?string $currentClientId = null): string
    {
        $v = $currentClientId ?? (string) config('mqtt-client.connections.default.client_id', 'laravel-iot-backend');
        while ($v !== '' && preg_match('/-(pub|sub|pool)-/', $v)) {
            $v = (string) preg_replace('/-(pub|sub|pool)-.*$/', '', $v);
        }

        return $v !== '' ? $v : 'laravel-iot-backend';
    }

    public static function hostSlug(): string
    {
        $host = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', (string) gethostname()));
        $host = trim($host, '-') ?: 'host';

        return $host;
    }
}
