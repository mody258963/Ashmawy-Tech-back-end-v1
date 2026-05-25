<?php

namespace App\Models\Iot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotSensorSlot extends Model
{
    protected $table = 'iot_sensor_slots';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'iot_device_id',
        'type',
        'label',
        'is_critical',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_critical' => 'boolean',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'iot_device_id');
    }
}
