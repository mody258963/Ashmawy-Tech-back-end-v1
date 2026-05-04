<?php

namespace App\Http\Resources\Iot;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Iot\IotDevice
 */
class IotDeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'device_uuid' => $this->device_uuid,
            'status' => $this->status,
            'last_seen' => $this->last_seen?->toIso8601String(),
            'mqtt_username' => $this->mqtt_username,
            'jwt_expires_at' => $this->jwt_expires_at?->toIso8601String(),
            'components_count' => $this->whenCounted('components'),
            'components' => IotComponentResource::collection($this->whenLoaded('components')),
        ];
    }
}
