<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Appointment::query()->with(['customer', 'technician'])->orderBy('scheduled_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('customer_id')) {
            $query->where('customer_id', (int) $request->integer('customer_id'));
        }
        if ($request->filled('technician_id')) {
            $query->where('technician_id', (int) $request->integer('technician_id'));
        }
        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $request->string('date')->toString());
        }

        return view('admin.appointments.index', [
            'appointments' => $query->paginate(20)->withQueryString(),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'technicians' => User::query()->whereIn('role', ['owner', 'technician'])->orderBy('name')->get(['id', 'name', 'role']),
            'statuses' => $this->statusOptions(),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'customer_id' => $request->string('customer_id')->toString(),
                'technician_id' => $request->string('technician_id')->toString(),
                'date' => $request->string('date')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.appointments.create', [
            'customers' => Customer::query()->orderBy('name')->get(),
            'technicians' => User::query()->whereIn('role', ['owner', 'technician'])->orderBy('name')->get(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? Appointment::STATUS_SCHEDULED;
        Appointment::query()->create($data);

        return redirect()->route('admin.appointments.index')->with('status', __('messages.appointment_created'));
    }

    public function edit(Appointment $appointment): View
    {
        return view('admin.appointments.edit', [
            'appointment' => $appointment,
            'customers' => Customer::query()->orderBy('name')->get(),
            'technicians' => User::query()->whereIn('role', ['owner', 'technician'])->orderBy('name')->get(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? Appointment::STATUS_SCHEDULED;
        $appointment->update($data);

        return redirect()->route('admin.appointments.index')->with('status', __('messages.appointment_updated'));
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();

        return redirect()->route('admin.appointments.index')->with('status', __('messages.appointment_deleted'));
    }

    /**
     * @return array<int, string>
     */
    private function statusOptions(): array
    {
        return [
            Appointment::STATUS_SCHEDULED,
            Appointment::STATUS_IN_PROGRESS,
            Appointment::STATUS_DONE,
            Appointment::STATUS_CANCELLED,
        ];
    }
}

