<?php

namespace App\Support\Iot;

final class IotTopic
{
    public static function base(int $iotUserId, string $deviceUuid): string
    {
        return 'iot/'.$iotUserId.'/'.$deviceUuid;
    }

    public static function componentSet(int $iotUserId, string $deviceUuid, int $channel): string
    {
        return self::base($iotUserId, $deviceUuid).'/component/'.$channel.'/set';
    }

    public static function componentStatus(int $iotUserId, string $deviceUuid, int $channel): string
    {
        return self::base($iotUserId, $deviceUuid).'/component/'.$channel.'/status';
    }

    public static function sensor(int $iotUserId, string $deviceUuid, string $type): string
    {
        return self::base($iotUserId, $deviceUuid).'/sensor/'.$type;
    }

    public static function deviceStatus(int $iotUserId, string $deviceUuid): string
    {
        return self::base($iotUserId, $deviceUuid).'/device/status';
    }

    /**
     * @return array{0: int, 1: string}|null
     */
    public static function parseBaseTopic(string $topic): ?array
    {
        $parts = explode('/', $topic);
        if (count($parts) < 3 || $parts[0] !== 'iot') {
            return null;
        }

        $userId = (int) $parts[1];
        $deviceUuid = $parts[2];

        if ($userId < 1 || $deviceUuid === '') {
            return null;
        }

        return [$userId, $deviceUuid];
    }
}
