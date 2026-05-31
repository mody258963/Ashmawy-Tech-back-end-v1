<?php

namespace App\Console\Commands;

use App\Models\Iot\IotDevice;
use App\Models\Iot\IotPushToken;
use App\Models\Iot\IotSensorSlot;
use App\Repository\Iot\IotSensorSlotRepository;
use App\Services\Iot\IotAppSession;
use App\Services\Iot\IotCriticalAlertService;
use App\Services\Iot\IotRealtimeStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IotDebugPushAlerts extends Command
{
    protected $signature = 'iot:debug-push
                            {--device= : iot_devices.id to inspect}
                            {--user= : iot_users.id (lists devices if --device omitted)}';

    protected $description = 'Diagnose why critical_alert FCM pushes are not firing (config, tokens, Redis, queue).';

    public function handle(
        IotSensorSlotRepository $sensorSlots,
        IotAppSession $appSession,
        IotRealtimeStore $realtime,
    ): int {
        $this->info('=== IoT critical push diagnostics ===');
        $this->newLine();

        $this->checkFcmConfig();
        $this->checkQueue();
        $this->checkPushTokens($this->option('user') ? (int) $this->option('user') : null);
        $this->newLine();

        $deviceId = $this->option('device') ? (int) $this->option('device') : null;
        $userId = $this->option('user') ? (int) $this->option('user') : null;

        $devices = IotDevice::query()
            ->when($deviceId, fn ($q) => $q->whereKey($deviceId))
            ->when($userId && ! $deviceId, fn ($q) => $q->where('iot_user_id', $userId))
            ->when(! $deviceId && ! $userId, fn ($q) => $q->orderByDesc('id')->limit(5))
            ->get();

        if ($devices->isEmpty()) {
            $this->warn('No devices found. Pass --device=ID or --user=ID.');

            return self::FAILURE;
        }

        foreach ($devices as $device) {
            $this->inspectDevice($device, $sensorSlots, $appSession, $realtime);
            $this->newLine();
        }

        $this->comment('Next steps:');
        $this->line('  1. php artisan iot:test-push --user=<iot_user_id>   # test FCM only');
        $this->line('  2. php artisan iot:test-push --device=<id> --simulate-door   # full alert path');
        $this->line('  3. php artisan queue:work --queue=iot   # must run for pushes');
        $this->line('  4. php artisan iot:mqtt-subscribe   # must run for live MQTT');
        $this->line('  5. grep critical_alert storage/logs/laravel.log');

        return self::SUCCESS;
    }

    private function checkFcmConfig(): void
    {
        $projectId = (string) config('iot.fcm.project_id', '');
        $path = $this->resolveCredentialsPath();

        $this->line('FCM_PROJECT_ID: '.($projectId !== '' ? $projectId : '*** NOT SET ***'));
        $this->line('FCM credentials: '.$path);
        if ($path === '' || ! is_readable($path)) {
            $this->error('  → Credentials file missing or not readable. Set FCM_PROJECT_ID and FCM_CREDENTIALS_PATH in .env');
        } else {
            $json = json_decode((string) file_get_contents($path), true);
            $email = is_array($json) ? ($json['client_email'] ?? '') : '';
            $this->info('  → OK (service account: '.($email !== '' ? $email : 'unknown').')');
        }

        $global = config('iot.critical_sensor_types', []);
        $this->line('IOT_CRITICAL_SENSOR_TYPES: '.implode(', ', $global));
    }

    private function checkQueue(): void
    {
        $connection = (string) config('queue.default');
        $iotQueue = (string) config('iot.queue', 'iot');
        $this->line('QUEUE_CONNECTION: '.$connection.' | IOT_QUEUE: '.$iotQueue);

        try {
            $pending = DB::table('jobs')->where('queue', $iotQueue)->count();
            $failed = DB::table('failed_jobs')->count();
            $this->line("Pending jobs on queue \"{$iotQueue}\": {$pending} | failed_jobs: {$failed}");
            if ($pending > 0) {
                $this->warn('  → Jobs waiting — run: php artisan queue:work --queue='.$iotQueue);
            }
        } catch (\Throwable $e) {
            $this->comment('  (Could not read jobs table: '.$e->getMessage().')');
        }
    }

    private function checkPushTokens(?int $userId): void
    {
        try {
            $query = IotPushToken::query();
            if ($userId !== null) {
                $query->where('iot_user_id', $userId);
            }
            $tokens = $query->get();
        } catch (\Throwable $e) {
            $this->error('  → Cannot read iot_push_tokens: '.$e->getMessage());
            $this->line('  Run: php artisan migrate');

            return;
        }

        $this->line('Registered FCM tokens: '.$tokens->count());
        if ($tokens->isEmpty()) {
            $this->error('  → No tokens. Mobile app must POST /api/v1/iot/push-tokens after login.');
        } else {
            foreach ($tokens as $t) {
                $suffix = strlen($t->token) > 8 ? '...'.substr($t->token, -8) : '***';
                $this->line("  user {$t->iot_user_id} | {$t->platform} | {$suffix}");
            }
        }
    }

    private function inspectDevice(
        IotDevice $device,
        IotSensorSlotRepository $sensorSlots,
        IotAppSession $appSession,
        IotRealtimeStore $realtime,
    ): void {
        $this->info("Device #{$device->id} — {$device->name} (user {$device->iot_user_id})");

        $sessionActive = $appSession->active((int) $device->id);
        $sessionKey = $appSession->redisKey((int) $device->id);
        if ($sessionActive) {
            $this->warn("  App session ACTIVE ({$sessionKey}) — pushes suppressed while app is foreground.");
            $this->line('  Fix: POST .../app/heartbeat with {"streaming":false} or wait for TTL.');
        } else {
            $this->info('  App session: inactive (OK for push)');
        }

        $slots = IotSensorSlot::query()->where('iot_device_id', $device->id)->get();
        if ($slots->isEmpty()) {
            $this->line('  Sensor slots: none (uses global IOT_CRITICAL_SENSOR_TYPES only)');
        } else {
            $this->line('  Sensor slots:');
            foreach ($slots as $slot) {
                $critical = $sensorSlots->isCriticalForDevice((int) $device->id, (string) $slot->type);
                $flag = $slot->is_critical ? 'critical=1' : 'critical=0';
                $effective = $critical ? '→ WILL alert' : '→ will NOT alert';
                $this->line("    {$slot->type} ({$flag}) {$effective}");
            }
        }

        $doorCritical = $sensorSlots->isCriticalForDevice((int) $device->id, 'door_status');
        $this->line('  door_status effective critical: '.($doorCritical ? 'yes' : 'no'));

        $sensors = $realtime->getSensorLatestAll((int) $device->id);
        if ($sensors === []) {
            $this->comment('  Redis sensor cache: empty (MQTT not received or cleared)');
        } else {
            $this->line('  Redis latest sensors:');
            foreach ($sensors as $type => $row) {
                $v = $row['value'] ?? null;
                $this->line('    '.$type.' = '.json_encode($v).' @ '.($row['recorded_at'] ?? ''));
            }
        }

        $door = $sensors['door_status']['value'] ?? null;
        if (is_array($door)) {
            $v = strtolower((string) ($door['v'] ?? ''));
            $isOpen = str_contains($v, 'open');
            $this->line('  door_status alert state: '.($isOpen ? 'OPEN (would alert on transition)' : 'not open'));
            if ($isOpen) {
                $this->comment('  If door was already open in Redis, opening again does NOT re-alert (same state).');
            }
        }
    }

    private function resolveCredentialsPath(): string
    {
        $path = (string) config('iot.fcm.credentials_path', '');
        if ($path === '') {
            return '';
        }
        if (! str_starts_with($path, '/') && ! preg_match('#^[A-Za-z]:\\\\#', $path)) {
            $path = base_path($path);
        }

        return $path;
    }
}
