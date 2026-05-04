<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MemberPackage extends Model
{
    protected $fillable = ['name', 'type', 'visit_quota', 'active_days'];
    public function memberships() { return $this->hasMany(Membership::class, 'package_id'); }
}
