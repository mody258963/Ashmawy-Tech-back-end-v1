<?php

namespace App\Models\Iot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IotComponent extends Model
{
    protected $table = 'iot_components';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'iot_device_id',
        'name',
        'type',
        'channel',
        'metadata',
        'last_state',
        'last_state_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_state' => 'array',
            'last_state_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'iot_device_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(IotDeviceAction::class, 'iot_component_id');
    }
}
