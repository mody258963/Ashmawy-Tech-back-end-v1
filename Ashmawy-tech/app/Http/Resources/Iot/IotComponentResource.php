<?php

namespace App\Http\Resources\Iot;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Iot\IotComponent
 */
class IotComponentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'channel' => $this->channel,
            'metadata' => $this->metadata,
            'last_state' => $this->last_state,
            'last_state_at' => $this->last_state_at?->toIso8601String(),
        ];
    }
}
