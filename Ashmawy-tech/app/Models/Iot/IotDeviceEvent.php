<?php

namespace App\Models\Iot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotDeviceEvent extends Model
{
    public $timestamps = false;

    protected $table = 'iot_device_events';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'iot_device_id',
        'type',
        'payload',
        'message_id',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'iot_device_id');
    }
}
