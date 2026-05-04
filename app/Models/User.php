<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'whatsapp', 'password',
        'role', 'avatar', 'birth_date', 'birth_place',
        'religion', 'occupation', 'address', 'member_number', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isAdmin(): bool { return in_array($this->role, ['super_admin', 'admin']); }
    public function isMember(): bool { return $this->role === 'member'; }

    public function bookings()        { return $this->hasMany(Booking::class); }
    public function transactions()    { return $this->hasMany(Transaction::class); }
    public function memberships()     { return $this->hasMany(Membership::class); }
    public function attendances()     { return $this->hasMany(AttendanceMember::class); }
    public function instructedClasses() { return $this->hasMany(FitnessClass::class, 'instructor_id'); }
}
