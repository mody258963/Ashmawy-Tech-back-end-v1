<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeviceRequest;
use App\Repository\Customer\CustomerRepository;
use App\Repository\Device\DeviceRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function __construct(
        private readonly DeviceRepository $devices,
        private readonly CustomerRepository $customers,
    ) {}

    public function index(): View
    {
        $search = request()->string('q')->toString();
        $query = \App\Models\Device::query()->with('customer');
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('type', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')
                    ->orWhere('model', 'like', '%'.$search.'%')
                    ->orWhere('serial_number', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%'));
            });
        }

        return view('admin.devices.index', [
            'devices' => $query->orderByDesc('id')->paginate(20)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.devices.create', [
            'customers' => $this->customers->all(),
        ]);
    }

    public function store(DeviceRequest $request): RedirectResponse
    {
        $this->devices->create($request->validated());

        return redirect()->route('admin.devices.index')->with('status', 'Device created.');
    }

    public function edit(int $device): View
    {
        return view('admin.devices.edit', [
            'device' => $this->devices->find($device),
            'customers' => $this->customers->all(),
        ]);
    }

    public function update(DeviceRequest $request, int $device): RedirectResponse
    {
        $this->devices->update($device, $request->validated());

        return redirect()->route('admin.devices.index')->with('status', 'Device updated.');
    }

    public function destroy(int $device): RedirectResponse
    {
        $this->devices->delete($device);

        return redirect()->route('admin.devices.index')->with('status', 'Device deleted.');
    }
}
