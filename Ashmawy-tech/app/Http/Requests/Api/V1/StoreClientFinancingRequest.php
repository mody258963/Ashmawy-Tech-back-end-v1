<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientFinancingRequest extends FormRequest
{
    public const INCOME_PROOF_OPTIONS = [
        'رخصه عربيه نقل',
        'سجل تجاري + بطاقه ضريبيه',
        'كشف حساب ٦ شهور',
        'حيازه ارض زراعيه',
    ];

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'car_type' => ['required', 'string', 'max:255'],
            'car_price' => ['required', 'numeric', 'min:0'],
            'down_payment' => ['required', 'numeric', 'min:0'],
            'income_proofs' => ['required', 'array', 'min:1'],
            'income_proofs.*' => ['required', 'string', Rule::in(self::INCOME_PROOF_OPTIONS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'الاسم',
            'phone' => 'رقم التليفون',
            'car_type' => 'نوع العربيه',
            'car_price' => 'سعر العربيه',
            'down_payment' => 'المقدم',
            'income_proofs' => 'اثباتات الدخل',
        ];
    }
}

