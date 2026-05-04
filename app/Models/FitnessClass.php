<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FitnessClass extends Model
{
    use HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'name', 'description', 'type', 'instructor_id',
        'schedule_at', 'duration_minutes', 'quota', 'price',
        'location', 'status',
    ];

    protected $casts = [
        'schedule_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function instructor() { return $this->belongsTo(User::class, 'instructor_id'); }
    public function bookings() { return $this->hasMany(Booking::class, 'class_id'); }

    public function getRemainingQuotaAttribute(): int
    {
        return $this->quota - $this->bookings()->where('status', 'confirmed')->count();
    }
}
