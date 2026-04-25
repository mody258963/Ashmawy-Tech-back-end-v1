<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FleetMaintenanceRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fleet_vehicle_id' => ['required', 'exists:fleet_vehicles,id'],
            'service_type' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string'],
            'cost' => ['required', 'numeric', 'min:0'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'service_date' => ['required', 'date'],
            'next_service_date' => ['nullable', 'date', 'after_or_equal:service_date'],
        ];
    }
}

