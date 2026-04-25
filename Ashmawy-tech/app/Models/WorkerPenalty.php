<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkerPenalty extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'amount',
        'reason',
        'applied_for_month',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'applied_for_month' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
