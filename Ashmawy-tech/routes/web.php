<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DeviceController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FleetMaintenanceController;
use App\Http\Controllers\Admin\FleetVehicleController;
use App\Http\Controllers\Admin\FollowUpController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PenaltyController;
use App\Http\Controllers\Admin\PerformanceController;
use App\Http\Controllers\Admin\SalaryController;
use App\Http\Controllers\Admin\SparePartController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        if (in_array(Auth::user()->role, ['owner', 'moderator'], true)) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::post('/locale', function (\Illuminate\Http\Request $request) {
    $data = $request->validate([
        'locale' => ['required', 'in:en,ar'],
    ]);
    $request->session()->put('locale', $data['locale']);

    return back();
})->name('locale.switch');

Route::get('/dashboard', function () {
    if (Auth::check() && in_array(Auth::user()->role, ['owner', 'moderator'], true)) {
        return redirect()->route('admin.dashboard');
    }

    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('branches', BranchController::class)->except(['show']);
    Route::resource('appointments', AppointmentController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::resource('devices', DeviceController::class)->except(['show']);
    Route::resource('orders', OrderController::class);
    Route::post('orders/{order}/notes', [OrderController::class, 'storeNote'])->name('orders.notes.store');
    Route::get('orders-calendar', [OrderController::class, 'pickupCalendar'])->name('orders.calendar');
    Route::resource('payments', PaymentController::class)->except(['show']);
    Route::resource('expenses', ExpenseController::class)->except(['show']);
    Route::resource('follow-ups', FollowUpController::class)->except(['show']);
    Route::resource('spare-parts', SparePartController::class)->except(['show']);
    Route::get('performance', [PerformanceController::class, 'index'])->name('performance.index');
    Route::resource('penalties', PenaltyController::class)->only(['index', 'create', 'store', 'destroy']);
    Route::resource('fleet-vehicles', FleetVehicleController::class)->except(['show']);
    Route::resource('fleet-maintenances', FleetMaintenanceController::class)->except(['show']);
    Route::resource('salaries', SalaryController::class)->except(['show']);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
