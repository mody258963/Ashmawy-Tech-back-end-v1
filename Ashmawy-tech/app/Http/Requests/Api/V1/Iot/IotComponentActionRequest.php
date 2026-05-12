<?php

namespace App\Http\Requests\Api\V1\Iot;

use Illuminate\Foundation\Http\FormRequest;

class IotComponentActionRequest extends FormRequest
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
        return [
            'action' => ['required', 'string', 'in:ON,OFF,TOGGLE,SET'],
            'value' => ['nullable', 'array'],
            // Wait for ESP .../component/{ch}/status echoing the command message_id (Redis poll).
            'wait_for_ack' => ['sometimes', 'boolean'],
            'wait_ack_timeout_ms' => ['sometimes', 'integer', 'min:0', 'max:30000'],
        ];
    }
}
