<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'moderator_id',
        'note',
        'next_follow_up_at',
    ];

    protected $casts = [
        'next_follow_up_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
