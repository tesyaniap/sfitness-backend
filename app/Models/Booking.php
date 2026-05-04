<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'class_id', 'status', 'notes', 'price', 'snap_token', 'midtrans_order_id', 'payment_status'];

    public function user() { return $this->belongsTo(User::class); }
    public function fitnessClass() { return $this->belongsTo(FitnessClass::class, 'class_id'); }
}
