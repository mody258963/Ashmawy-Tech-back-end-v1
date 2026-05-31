<?php

use App\Console\Commands\IotDebugPushAlerts;
use App\Console\Commands\IotTestPush;
use App\Console\Commands\MakeRepositoryModule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/iot.php'));
        },
    )->withCommands([
        IotDebugPushAlerts::class,
        IotTestPush::class,
        MakeRepositoryModule::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->web(append: [
            \App\Http\Middleware\SetLocaleFromSession::class,
        ]);
        $middleware->redirectGuestsTo(function (Request $request): string {
            if ($request->is('iot') || $request->is('iot/*')) {
                return route('iot.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request): string {
            if ($request->is('iot') || $request->is('iot/*')) {
                return route('iot.dashboard');
            }

            return route('dashboard');
        });

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'admin.owner' => \App\Http\Middleware\EnsureUserIsOwner::class,
            'iot.user' => \App\Http\Middleware\EnsureIotUser::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
