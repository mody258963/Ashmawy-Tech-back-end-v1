<?php

namespace App\Http\Requests\Iot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IotWebComponentStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => [
                'required',
                'string',
                Rule::in(['switch', 'dimmer', 'motor', 'sensor', 'lock', 'valve', 'hvac', 'generic']),
            ],
            'channel' => [
                'required',
                'integer',
                'min:1',
                'max:255',
                Rule::unique('iot_components', 'channel')->where(fn ($q) => $q->where('iot_device_id', $deviceId)),
            ],
        ];
    }
}
