<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppointmentStatusRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    Appointment::STATUS_SCHEDULED,
                    Appointment::STATUS_IN_PROGRESS,
                    Appointment::STATUS_DONE,
                    Appointment::STATUS_CANCELLED,
                ]),
            ],
        ];
    }
}

