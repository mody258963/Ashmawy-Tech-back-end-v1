<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customerId = $this->route('customer');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers', 'phone')->ignore($customerId),
            ],
            'address' => ['nullable', 'string'],
            'address_link' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:new,contacted,follow_up,converted,rejected'],
            'rejection_reason' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['required', 'exists:branches,id'],
        ];
    }
}

