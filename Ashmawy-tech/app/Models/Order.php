<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'device_id',
        'customer_id',
        'collector_id',
        'technician_id',
        'estimated_cost',
        'final_cost',
        'status',
        'approved',
        'received_at',
        'delivered_at',
        'branch_id',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'estimated_cost' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'received_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function spareParts()
    {
        return $this->belongsToMany(SparePart::class, 'order_spare_parts')
            ->withPivot('quantity', 'unit_price');
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class);
    }
}
