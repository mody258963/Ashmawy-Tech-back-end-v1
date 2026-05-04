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
        ];
    }
}
