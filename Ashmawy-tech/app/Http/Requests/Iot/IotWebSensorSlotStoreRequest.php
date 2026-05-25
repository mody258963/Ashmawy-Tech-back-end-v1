<?php

namespace App\Http\Requests\Iot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IotWebSensorSlotStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $deviceId = (int) $this->route('device');

        return [
            'type' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('iot_sensor_slots', 'type')->where(fn ($q) => $q->where('iot_device_id', $deviceId)),
            ],
            'label' => ['nullable', 'string', 'max:255'],
            'is_critical' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('type')) {
            $this->merge(['type' => strtolower((string) $this->input('type'))]);
        }
    }
}
