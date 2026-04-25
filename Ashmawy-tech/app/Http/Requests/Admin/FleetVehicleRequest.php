<?php

namespace App\Http\Requests\Admin;

use App\Models\FleetVehicle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FleetVehicleRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $fleetVehicle = $this->route('fleetVehicle');
        $fleetVehicleId = $fleetVehicle instanceof FleetVehicle ? $fleetVehicle->id : null;

        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'type' => ['required', 'in:van,car'],
            'name' => ['required', 'string', 'max:255'],
            'plate_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fleet_vehicles', 'plate_number')->ignore($fleetVehicleId),
            ],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'service_interval_km' => ['nullable', 'integer', 'min:100'],
            'last_service_at' => ['nullable', 'date'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}

