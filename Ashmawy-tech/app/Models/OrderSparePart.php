<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OrderSparePart extends Pivot
{
    use HasFactory;

    protected $table = 'order_spare_parts';

    protected $fillable = [
        'order_id',
        'spare_part_id',
        'quantity',
        'unit_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function sparePart()
    {
        return $this->belongsTo(SparePart::class);
    }
}
