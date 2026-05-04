<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AttendanceMember extends Model
{
    protected $fillable = ['attendance_id', 'membership_id', 'user_id', 'guest_name', 'type', 'price_paid'];
    protected $casts    = ['price_paid' => 'decimal:2'];

    public function attendance() { return $this->belongsTo(Attendance::class); }
    public function membership() { return $this->belongsTo(Membership::class); }
    public function user()       { return $this->belongsTo(User::class); }
}
