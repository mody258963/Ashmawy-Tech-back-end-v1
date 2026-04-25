<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FleetMaintenanceRequest;
use App\Models\Expense;
use App\Models\FleetMaintenance;
use App\Models\FleetVehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FleetMaintenanceController extends Controller
{
    public function index(): View
    {
        return view('admin.fleet-maintenances.index', [
            'items' => FleetMaintenance::query()->with(['vehicle', 'branch'])->latest('service_date')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.fleet-maintenances.create', [
            'vehicles' => FleetVehicle::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(FleetMaintenanceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $vehicle = FleetVehicle::query()->findOrFail($data['fleet_vehicle_id']);
        $data['branch_id'] = $vehicle->branch_id;
        $data['created_by'] = $request->user()->id;
        FleetMaintenance::query()->create($data);
        Expense::query()->create([
            'branch_id' => $vehicle->branch_id,
            'title' => 'Fleet: '.$vehicle->name.' - '.$data['service_type'],
            'amount' => $data['cost'],
            'description' => $data['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.fleet-maintenances.index')->with('status', 'Maintenance and expense recorded.');
    }

    public function edit(FleetMaintenance $fleetMaintenance): View
    {
        return view('admin.fleet-maintenances.edit', [
            'item' => $fleetMaintenance,
            'vehicles' => FleetVehicle::query()->where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(FleetMaintenanceRequest $request, FleetMaintenance $fleetMaintenance): RedirectResponse
    {
        $data = $request->validated();
        $vehicle = FleetVehicle::query()->findOrFail($data['fleet_vehicle_id']);
        $data['branch_id'] = $vehicle->branch_id;
        $fleetMaintenance->update($data);

        return redirect()->route('admin.fleet-maintenances.index')->with('status', 'Maintenance updated.');
    }

    public function destroy(FleetMaintenance $fleetMaintenance): RedirectResponse
    {
        $fleetMaintenance->delete();

        return redirect()->route('admin.fleet-maintenances.index')->with('status', 'Maintenance deleted.');
    }
}
