<?php

use App\Http\Controllers\Iot\AuthenticatedSessionController;
use App\Http\Controllers\Iot\DashboardController;
use App\Http\Controllers\Iot\DeviceController;
use Illuminate\Support\Facades\Route;

Route::prefix('iot')->name('iot.')->group(function (): void {
    Route::middleware('guest:iot-web')->group(function (): void {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.attempt');
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:iot-web')
        ->name('logout');

    Route::middleware(['auth:iot-web', 'iot.user'])->group(function (): void {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::get('devices/{device}', [DeviceController::class, 'show'])->name('devices.show');
        Route::post('devices/{device}/components/{component}/action', [DeviceController::class, 'action'])
            ->name('devices.components.action');
        Route::post('devices/{device}/jwt/regenerate', [DeviceController::class, 'regenerateJwt'])
            ->name('devices.jwt.regenerate');
    });
});
