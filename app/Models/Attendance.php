<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['class_id', 'instructor_id', 'date', 'notes'];
    protected $casts    = ['date' => 'date'];

    public function fitnessClass() { return $this->belongsTo(FitnessClass::class, 'class_id'); }
    public function instructor()   { return $this->belongsTo(User::class, 'instructor_id'); }
    public function members()      { return $this->hasMany(AttendanceMember::class); }

    public function getTotalPresentAttribute(): int  { return $this->members()->count(); }
    public function getTotalMemberAttribute(): int   { return $this->members()->where('type', 'member')->count(); }
    public function getTotalSingleAttribute(): int   { return $this->members()->where('type', 'single_visit')->count(); }
}
