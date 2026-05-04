<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Membership extends Model
{
    protected $fillable = [
        'user_id', 'class_id', 'package_id', 'price',
        'visit_quota', 'visit_used', 'visit_remaining',
        'start_date', 'expired_date', 'status',
        'payment_method', 'payment_status', 'snap_token', 'notes',
    ];

    protected $casts = [
        'start_date'    => 'date',
        'expired_date'  => 'date',
        'price'         => 'decimal:2',
    ];

    public function user()    { return $this->belongsTo(User::class); }
    public function class_()  { return $this->belongsTo(FitnessClass::class, 'class_id'); }
    public function package() { return $this->belongsTo(MemberPackage::class, 'package_id'); }
    public function attendances() { return $this->hasMany(AttendanceMember::class); }

    // Cek apakah membership masih aktif
    public function isActive(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->visit_remaining <= 0) return false;
        if ($this->expired_date && Carbon::today()->gt($this->expired_date)) return false;
        return true;
    }

    // Auto update status jika expired
    public function checkAndUpdateStatus(): void
    {
        if ($this->status === 'active') {
            if ($this->visit_remaining <= 0) {
                $this->update(['status' => 'used_up']);
            } elseif ($this->expired_date && Carbon::today()->gt($this->expired_date)) {
                $this->update(['status' => 'expired']);
            }
        }
    }
}
