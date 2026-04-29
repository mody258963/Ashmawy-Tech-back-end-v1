<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OrderRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'branch_id' => $this->filled('branch_id') ? $this->input('branch_id') : null,
            'collector_id' => $this->filled('collector_id') ? $this->input('collector_id') : null,
            'technician_id' => $this->filled('technician_id') ? $this->input('technician_id') : null,
            'final_cost' => $this->emptyStringToNull('final_cost'),
            'received_at' => $this->emptyStringToNull('received_at'),
            'delivered_at' => $this->emptyStringToNull('delivered_at'),
            'service_mode' => $this->input('service_mode', Order::SERVICE_MODE_WORKSHOP),
            'home_service_stage' => $this->emptyStringToNull('home_service_stage'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'exists:devices,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'collector_id' => ['nullable', 'exists:users,id'],
            'technician_id' => ['nullable', 'exists:users,id'],
            'estimated_cost' => ['required', 'numeric', 'min:0'],
            'final_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:'.implode(',', Order::STATUSES)],
            'service_mode' => ['required', 'in:'.Order::SERVICE_MODE_WORKSHOP.','.Order::SERVICE_MODE_HOME],
            'home_service_stage' => ['nullable', 'in:'.implode(',', Order::HOME_STAGES)],
            'approved' => ['sometimes', 'boolean'],
            'received_at' => ['nullable', 'date', 'required_if:status,pending_pickup'],
            'delivered_at' => ['nullable', 'date'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'parts' => ['nullable', 'array'],
            'parts.*.spare_part_id' => ['nullable', 'integer', 'exists:spare_parts,id'],
            'parts.*.quantity' => ['nullable', 'integer', 'min:1'],
            'parts.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validatedOrderData(): array
    {
        $data = $this->validated();
        $data['approved'] = $this->boolean('approved');
        if (($data['service_mode'] ?? Order::SERVICE_MODE_WORKSHOP) !== Order::SERVICE_MODE_HOME) {
            $data['home_service_stage'] = null;
        }
        if (($data['service_mode'] ?? Order::SERVICE_MODE_WORKSHOP) === Order::SERVICE_MODE_HOME && empty($data['home_service_stage'])) {
            $data['home_service_stage'] = Order::HOME_STAGE_SCHEDULED;
        }
        if (($data['service_mode'] ?? Order::SERVICE_MODE_WORKSHOP) === Order::SERVICE_MODE_HOME) {
            $data['collector_id'] = null;
            if (($data['status'] ?? null) === 'pending_pickup') {
                $data['status'] = 'received';
            }
        }
        unset($data['parts']);

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function validatedSpareParts(): array
    {
        $parts = $this->validated('parts', []);
        $out = [];

        foreach ($parts as $index => $part) {
            $hasAnyValue = ($part['spare_part_id'] ?? null) !== null
                || ($part['quantity'] ?? null) !== null
                || ($part['unit_price'] ?? null) !== null;

            if (! $hasAnyValue) {
                continue;
            }

            if (($part['spare_part_id'] ?? null) === null || ($part['quantity'] ?? null) === null) {
                throw new HttpException(422, 'If you add inventory row '.($index + 1).', choose part and quantity.');
            }

            $out[] = $part;
        }

        return $out;
    }

    private function emptyStringToNull(string $field): mixed
    {
        $value = $this->input($field);

        return $value === '' || $value === null ? null : $value;
    }
}

