<?php

namespace App\Console\Commands;

use App\Jobs\Iot\SendIotAlertPushJob;
use App\Models\Iot\IotDevice;
use App\Models\Iot\IotPushToken;
use App\Services\Iot\FcmNotificationService;
use App\Services\Iot\IotAppSession;
use App\Services\Iot\IotCriticalAlertService;
use Illuminate\Console\Command;

class IotTestPush extends Command
{
    protected $signature = 'iot:test-push
                            {--user= : iot_users.id (required unless --device set)}
                            {--device= : iot_devices.id}
                            {--sync : Run FCM immediately (no queue)}
                            {--simulate-door : Run critical_alert path with door open payload}
                            {--clear-session : Clear app foreground session for device (allow push)}';

    protected $description = 'Send a test FCM push or simulate a critical door_status alert.';

    public function handle(
        FcmNotificationService $fcm,
        IotCriticalAlertService $criticalAlerts,
        IotAppSession $appSession,
    ): int {
        $device = $this->resolveDevice();
        if ($device === null) {
            return self::FAILURE;
        }

        $userId = (int) $device->iot_user_id;
        $tokenCount = IotPushToken::query()->where('iot_user_id', $userId)->count();

        if ($this->option('clear-session')) {
            $appSession->clear((int) $device->id);
            $this->info("Cleared app session for device #{$device->id}.");
        }

        if ($tokenCount === 0) {
            $this->error("No FCM tokens for iot_user_id {$userId}. Register via POST /api/v1/iot/push-tokens from the app.");

            return self::FAILURE;
        }

        $this->info("Device #{$device->id} ({$device->name}), tokens: {$tokenCount}");

        if ($this->option('simulate-door')) {
            $appSession->clear((int) $device->id);
            $this->info('Simulating door_status open (previous=null)…');
            $criticalAlerts->maybeNotify($device, 'door_status', ['v' => 'door open', 'seq' => 1], null);
            $this->info('maybeNotify() finished. Check logs for critical_alert / FCM lines.');

            if ($this->option('sync')) {
                $this->warn('--sync with --simulate-door: job may still be queued; processing queue job manually…');
            }

            if (! $this->option('sync')) {
                $this->line('Run: php artisan queue:work --queue='.config('iot.queue', 'iot').' --once');
            }

            return self::SUCCESS;
        }

        $data = [
            'type' => 'critical_alert',
            'device_id' => (string) $device->id,
            'sensor_type' => 'test',
        ];
        $title = 'Ashmawy test';
        $body = 'Test push at '.now()->toIso8601String();

        if ($this->option('sync')) {
            $tokens = IotPushToken::query()->where('iot_user_id', $userId)->pluck('token')->all();
            $this->info('Sending FCM synchronously…');
            $fcm->sendToTokens($tokens, $title, $body, $data, highPriority: true);
            $this->info('Done. Check phone and storage/logs/laravel.log');

            return self::SUCCESS;
        }

        SendIotAlertPushJob::dispatch($userId, (int) $device->id, $title, $body, $data);
        $this->info('Queued SendIotAlertPushJob. Run: php artisan queue:work --queue='.config('iot.queue', 'iot').' --once');

        return self::SUCCESS;
    }

    private function resolveDevice(): ?IotDevice
    {
        if ($this->option('device')) {
            $device = IotDevice::query()->find((int) $this->option('device'));
            if ($device === null) {
                $this->error('Device not found.');

                return null;
            }

            return $device;
        }

        $userId = $this->option('user');
        if ($userId === null) {
            $this->error('Pass --user=<iot_users.id> or --device=<iot_devices.id>');

            return null;
        }

        $device = IotDevice::query()->where('iot_user_id', (int) $userId)->orderByDesc('id')->first();
        if ($device === null) {
            $this->error('No device for that user.');

            return null;
        }

        return $device;
    }
}
