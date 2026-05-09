<?php

namespace App\Http\Resources\Iot;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Iot\IotSensorData
 */
class IotSensorDataResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->when(isset($this->id), $this->id),
            'type' => $this->type,
            'value' => $this->value,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
        ];
    }
}
