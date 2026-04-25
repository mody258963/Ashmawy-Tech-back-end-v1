<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SparePart extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'unit_type',
        'quantity',
        'cost_price',
        'selling_price',
        'branch_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_spare_parts')
            ->withPivot('quantity', 'unit_price');
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class)->orderByDesc('created_at');
    }
}
