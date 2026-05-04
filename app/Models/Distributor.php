<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Distributor extends Model
{
    protected $fillable = ['name', 'phone', 'address', 'contact_person'];
    public function purchases() { return $this->hasMany(Purchase::class); }
    public function debts()     { return $this->hasMany(Debt::class); }
    public function returns()   { return $this->hasMany(ReturnOrder::class); }
}
