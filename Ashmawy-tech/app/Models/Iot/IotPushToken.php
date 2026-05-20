<?php

namespace App\Models\Iot;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotPushToken extends Model
{
    protected $table = 'iot_push_tokens';

    protected $fillable = [
        'iot_user_id',
        'token',
        'platform',
    ];

    public function iotUser(): BelongsTo
    {
        return $this->belongsTo(IotUser::class, 'iot_user_id');
    }
}
