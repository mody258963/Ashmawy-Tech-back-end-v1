<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentUpdateRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'required', 'exists:customers,id'],
            'technician_id' => [
                'nullable',
                Rule::exists('users', 'id')->whereIn('role', ['owner', 'technician']),
            ],
            'scheduled_at' => ['sometimes', 'required', 'date'],
            'status' => [
                'nullable',
                Rule::in([
                    Appointment::STATUS_SCHEDULED,
                    Appointment::STATUS_IN_PROGRESS,
                    Appointment::STATUS_DONE,
                    Appointment::STATUS_CANCELLED,
                ]),
            ],
            'address' => ['nullable', 'string', 'max:5000'],
            'address_link' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string'],
        ];
    }
}

