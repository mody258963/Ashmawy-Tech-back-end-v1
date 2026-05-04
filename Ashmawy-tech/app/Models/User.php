<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'branch_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdCustomers()
    {
        return $this->hasMany(Customer::class, 'created_by');
    }

    public function technicianOrders()
    {
        return $this->hasMany(Order::class, 'technician_id');
    }

    public function collectedOrders()
    {
        return $this->hasMany(Order::class, 'collector_id');
    }

    public function paymentsReceived()
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    public function technicianAppointments()
    {
        return $this->hasMany(Appointment::class, 'technician_id');
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}
