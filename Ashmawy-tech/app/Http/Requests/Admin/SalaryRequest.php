<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SalaryRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'for_month' => ['required', 'date'],
            'base_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,approved,paid'],
        ];
    }
}

