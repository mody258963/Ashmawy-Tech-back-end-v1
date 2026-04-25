<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianStat extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'technician_id',
        'total_orders',
        'total_profit',
        'commission_earned',
        'month',
    ];

    protected $casts = [
        'total_orders' => 'integer',
        'total_profit' => 'decimal:2',
        'commission_earned' => 'decimal:2',
        'month' => 'date',
    ];

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
