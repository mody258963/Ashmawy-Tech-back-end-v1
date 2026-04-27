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
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
    }

    public function boot(): void
    {
        Paginator::useBootstrap();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::define('access-admin', function ($user) {
            return $user && in_array($user->role, ['owner', 'moderator'], true);
        });
    }
}
