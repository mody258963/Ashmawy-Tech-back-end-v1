<?php

namespace App\Models\Iot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IotDevice extends Model
{
    protected $table = 'iot_devices';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'iot_user_id',
        'device_uuid',
        'name',
        'location',
        'notes',
        'status',
        'last_seen',
        'mqtt_username',
        'mqtt_jwt_token',
        'jwt_expires_at',
        'secret_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'device_uuid' => 'string',
            'last_seen' => 'datetime',
            'jwt_expires_at' => 'datetime',
        ];
    }

    public function iotUser(): BelongsTo
    {
        return $this->belongsTo(IotUser::class, 'iot_user_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(IotComponent::class, 'iot_device_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(IotDeviceAction::class, 'iot_device_id');
    }

    public function sensorData(): HasMany
    {
        return $this->hasMany(IotSensorData::class, 'iot_device_id');
    }

    public function sensorSlots(): HasMany
    {
        return $this->hasMany(IotSensorSlot::class, 'iot_device_id');
    }

    public function iotEvents(): HasMany
    {
        return $this->hasMany(IotDeviceEvent::class, 'iot_device_id');
    }
}
