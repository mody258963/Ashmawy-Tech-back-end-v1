<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetVehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'type',
        'name',
        'plate_number',
        'odometer',
        'service_interval_km',
        'last_service_at',
        'active',
    ];

    protected $casts = [
        'odometer' => 'integer',
        'service_interval_km' => 'integer',
        'last_service_at' => 'date',
        'active' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function maintenances()
    {
        return $this->hasMany(FleetMaintenance::class);
    }
}
