<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'fleet_vehicle_id',
        'branch_id',
        'service_type',
        'notes',
        'cost',
        'odometer',
        'service_date',
        'next_service_date',
        'created_by',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'odometer' => 'integer',
        'service_date' => 'date',
        'next_service_date' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(FleetVehicle::class, 'fleet_vehicle_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
