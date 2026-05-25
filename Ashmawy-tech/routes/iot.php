<?php

use App\Http\Controllers\Iot\AuthenticatedSessionController;
use App\Http\Controllers\Iot\ComponentController;
use App\Http\Controllers\Iot\DashboardController;
use App\Http\Controllers\Iot\DeviceController;
use App\Http\Controllers\Iot\SensorSlotController;
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
        Route::get('devices/create', [DeviceController::class, 'create'])->name('devices.create');
        Route::post('devices', [DeviceController::class, 'store'])->name('devices.store');
        Route::get('devices/{device}', [DeviceController::class, 'show'])->name('devices.show');
        Route::get('devices/{device}/edit', [DeviceController::class, 'edit'])->name('devices.edit');
        Route::put('devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
        Route::delete('devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
        Route::post('devices/{device}/jwt/regenerate', [DeviceController::class, 'regenerateJwt'])
            ->name('devices.jwt.regenerate');
        Route::post('devices/{device}/components/{component}/action', [DeviceController::class, 'action'])
            ->name('devices.components.action');

        Route::post('devices/{device}/components', [ComponentController::class, 'store'])
            ->name('devices.components.store');
        Route::put('devices/{device}/components/{component}', [ComponentController::class, 'update'])
            ->name('devices.components.update');
        Route::delete('devices/{device}/components/{component}', [ComponentController::class, 'destroy'])
            ->name('devices.components.destroy');

        Route::post('devices/{device}/sensor-slots', [SensorSlotController::class, 'store'])
            ->name('devices.sensor-slots.store');
        Route::delete('devices/{device}/sensor-slots/{slot}', [SensorSlotController::class, 'destroy'])
            ->name('devices.sensor-slots.destroy');
    });
});
