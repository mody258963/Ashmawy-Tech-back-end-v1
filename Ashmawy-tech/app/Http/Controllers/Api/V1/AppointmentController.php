<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\AppointmentStatusRequest;
use App\Http\Requests\Api\V1\AppointmentStoreRequest;
use App\Http\Requests\Api\V1\AppointmentUpdateRequest;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! in_array($user?->role, ['owner', 'technician', 'moderator'], true)) {
            abort(403);
        }

        $query = Appointment::query()
            ->with(['customer:id,name,phone', 'technician:id,name,phone,role'])
            ->orderBy('scheduled_at');

        if (in_array($user?->role, ['technician', 'owner'], true)) {
            $query->where('technician_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('date')) {
            $query->whereDate('scheduled_at', $request->string('date')->toString());
        }

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        return response()->json($query->paginate($perPage));
    }

    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeView($request, $appointment);

        return response()->json(
            $appointment->load(['customer', 'technician'])
        );
    }

    public function store(AppointmentStoreRequest $request): JsonResponse
    {
        $this->authorizeManage($request);

        $data = $request->validated();
        $data['status'] = $data['status'] ?? Appointment::STATUS_SCHEDULED;
        $appointment = Appointment::query()->create($data);

        return response()->json($appointment->load(['customer', 'technician']), 201);
    }

    public function update(AppointmentUpdateRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeUpdate($request, $appointment);

        $appointment->update($request->validated());

        return response()->json($appointment->fresh(['customer', 'technician']));
    }

    public function updateStatus(AppointmentStatusRequest $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeUpdate($request, $appointment);

        $appointment->update([
            'status' => $request->validated('status'),
        ]);

        return response()->json($appointment->fresh(['customer', 'technician']));
    }

    private function authorizeManage(Request $request): void
    {
        if (! in_array($request->user()?->role, ['owner', 'moderator'], true)) {
            abort(403);
        }
    }

    private function authorizeView(Request $request, Appointment $appointment): void
    {
        $user = $request->user();
        if ($user?->role === 'moderator') {
            return;
        }
        if (in_array($user?->role, ['owner', 'technician'], true) && (int) $appointment->technician_id === (int) $user->id) {
            return;
        }

        abort(403);
    }

    private function authorizeUpdate(Request $request, Appointment $appointment): void
    {
        $user = $request->user();
        if ($user?->role === 'moderator') {
            return;
        }
        if (in_array($user?->role, ['owner', 'technician'], true) && (int) $appointment->technician_id === (int) $user->id) {
            return;
        }

        abort(403);
    }
}

