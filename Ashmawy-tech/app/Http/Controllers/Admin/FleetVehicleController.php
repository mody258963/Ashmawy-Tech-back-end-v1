<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FleetVehicleRequest;
use App\Models\Branch;
use App\Models\FleetVehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FleetVehicleController extends Controller
{
    public function index(): View
    {
        return view('admin.fleet-vehicles.index', [
            'vehicles' => FleetVehicle::query()->with('branch')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.fleet-vehicles.create', [
            'branches' => Branch::query()->orderBy('name')->get(),
        ]);
    }

    public function store(FleetVehicleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        FleetVehicle::query()->create($data);

        return redirect()->route('admin.fleet-vehicles.index')->with('status', 'Vehicle added.');
    }

    public function edit(FleetVehicle $fleetVehicle): View
    {
        return view('admin.fleet-vehicles.edit', [
            'vehicle' => $fleetVehicle,
            'branches' => Branch::query()->orderBy('name')->get(),
        ]);
    }

    public function update(FleetVehicleRequest $request, FleetVehicle $fleetVehicle): RedirectResponse
    {
        $data = $request->validated();
        $data['active'] = $request->boolean('active', true);
        $fleetVehicle->update($data);

        return redirect()->route('admin.fleet-vehicles.index')->with('status', 'Vehicle updated.');
    }

    public function destroy(FleetVehicle $fleetVehicle): RedirectResponse
    {
        $fleetVehicle->delete();

        return redirect()->route('admin.fleet-vehicles.index')->with('status', 'Vehicle removed.');
    }
}
