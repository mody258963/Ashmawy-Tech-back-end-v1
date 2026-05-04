<?php

namespace App\Models\Iot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotSensorData extends Model
{
    public $timestamps = false;

    protected $table = 'iot_sensor_data';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'iot_device_id',
        'type',
        'value',
        'message_id',
        'recorded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'iot_device_id');
    }
}
