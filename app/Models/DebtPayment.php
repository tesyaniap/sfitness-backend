<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DebtPayment extends Model
{
    protected $fillable = ['debt_id','amount','payment_method','notes'];
    protected $casts    = ['amount' => 'decimal:2'];
    public function debt() { return $this->belongsTo(Debt::class); }
}
