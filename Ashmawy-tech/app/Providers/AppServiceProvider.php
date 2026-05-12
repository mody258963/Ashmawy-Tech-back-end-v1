<?php

namespace App\Providers;

use App\Repository\Branch\BranchRepository;
use App\Repository\Branch\Eloquent\BranchEloquent;
use App\Repository\Customer\CustomerRepository;
use App\Repository\Customer\Eloquent\CustomerEloquent;
use App\Repository\Device\DeviceRepository;
use App\Repository\Device\Eloquent\DeviceEloquent;
use App\Repository\Expense\Eloquent\ExpenseEloquent;
use App\Repository\Expense\ExpenseRepository;
use App\Repository\FollowUp\Eloquent\FollowUpEloquent;
use App\Repository\FollowUp\FollowUpRepository;
use App\Repository\Iot\Eloquent\IotComponentEloquent;
use App\Repository\Iot\Eloquent\IotDeviceEloquent;
use App\Repository\Iot\Eloquent\IotSensorDataEloquent;
use App\Repository\Iot\IotComponentRepository;
use App\Repository\Iot\IotDeviceRepository;
use App\Repository\Iot\IotSensorDataRepository;
use App\Repository\Order\Eloquent\OrderEloquent;
use App\Repository\Order\OrderRepository;
use App\Repository\OrderNote\Eloquent\OrderNoteEloquent;
use App\Repository\OrderNote\OrderNoteRepository;
use App\Repository\OrderStatusHistory\Eloquent\OrderStatusHistoryEloquent;
use App\Repository\OrderStatusHistory\OrderStatusHistoryRepository;
use App\Repository\Payment\Eloquent\PaymentEloquent;
use App\Repository\Payment\PaymentRepository;
use App\Repository\SparePart\Eloquent\SparePartEloquent;
use App\Repository\SparePart\SparePartRepository;
use App\Repository\User\Eloquent\UserEloquent;
use App\Repository\User\UserRepository;
use App\Support\Iot\MqttClientId;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BranchRepository::class, BranchEloquent::class);
        $this->app->bind(CustomerRepository::class, CustomerEloquent::class);
        $this->app->bind(DeviceRepository::class, DeviceEloquent::class);
        $this->app->bind(OrderRepository::class, OrderEloquent::class);
        $this->app->bind(PaymentRepository::class, PaymentEloquent::class);
        $this->app->bind(SparePartRepository::class, SparePartEloquent::class);
        $this->app->bind(UserRepository::class, UserEloquent::class);
        $this->app->bind(ExpenseRepository::class, ExpenseEloquent::class);
        $this->app->bind(FollowUpRepository::class, FollowUpEloquent::class);
        $this->app->bind(OrderNoteRepository::class, OrderNoteEloquent::class);
        $this->app->bind(OrderStatusHistoryRepository::class, OrderStatusHistoryEloquent::class);
        $this->app->bind(IotDeviceRepository::class, IotDeviceEloquent::class);
        $this->app->bind(IotComponentRepository::class, IotComponentEloquent::class);
        $this->app->bind(IotSensorDataRepository::class, IotSensorDataEloquent::class);
    }

    public function boot(): void
    {
        $this->assignDefaultMqttClientIdForProcess();

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        Paginator::useBootstrap();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::define('access-admin', function ($user) {
            return $user && in_array($user->role, ['owner', 'moderator'], true);
        });
    }

    /**
     * php-mqtt may open the default connection with config client_id before MqttPublisherService runs.
     * Never leave the bare MQTT_CLIENT_ID on EMQX (duplicate FPM / queue workers → EOF). The
     * `iot:mqtt-subscribe` command overwrites this with a `-sub-` id in IotMqttSubscribe::handle.
     */
    private function assignDefaultMqttClientIdForProcess(): void
    {
        if ($this->app->runningInConsole()) {
            $argv = $_SERVER['argv'] ?? [];
            if (($argv[1] ?? '') === 'iot:mqtt-subscribe') {
                return;
            }
        }

        $key = 'mqtt-client.connections.default.client_id';
        $current = (string) config($key, '');
        if (str_contains($current, '-pool-') || str_contains($current, '-sub-')) {
            return;
        }

        $base = MqttClientId::logicalBase($current !== '' ? $current : null);
        Config::set($key, $base.'-pool-'.MqttClientId::hostSlug().'-'.getmypid());
    }
}
