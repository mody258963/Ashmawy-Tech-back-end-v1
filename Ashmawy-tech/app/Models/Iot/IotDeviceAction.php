<?php

namespace App\Models\Iot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotDeviceAction extends Model
{
    public $timestamps = false;

    protected $table = 'iot_device_actions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'iot_device_id',
        'iot_component_id',
        'action',
        'value',
        'triggered_by',
        'triggered_by_id',
        'message_id',
        'ack_outcome',
        'ack_payload',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'array',
            'ack_payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'iot_device_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(IotComponent::class, 'iot_component_id');
    }
}
