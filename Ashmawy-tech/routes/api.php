<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\OrderSparePartController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\WorkerOrderFlowController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', MeController::class);

        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);

        Route::post('payments', [PaymentController::class, 'store']);
        Route::get('orders/{order}/balance', [PaymentController::class, 'balance']);

        Route::post('orders/{order}/spare-parts', [OrderSparePartController::class, 'store']);

        Route::patch('collector/orders/{order}/pickup-from-customer', [WorkerOrderFlowController::class, 'collectorPickupFromCustomer']);
        Route::patch('technician/orders/{order}/finish-fixing', [WorkerOrderFlowController::class, 'technicianFinishFixing']);
        Route::get('collector/orders/pending-delivery', [WorkerOrderFlowController::class, 'collectorPendingDelivery']);
        Route::patch('collector/orders/{order}/mark-delivered', [WorkerOrderFlowController::class, 'collectorMarkDelivered']);

        Route::get('appointments', [AppointmentController::class, 'index']);
        Route::get('appointments/{appointment}', [AppointmentController::class, 'show']);
        Route::post('appointments', [AppointmentController::class, 'store']);
        Route::patch('appointments/{appointment}', [AppointmentController::class, 'update']);
        Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
    });
});
