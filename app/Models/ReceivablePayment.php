<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReceivablePayment extends Model
{
    protected $fillable = ['receivable_id','amount','payment_method','notes'];
    protected $casts    = ['amount' => 'decimal:2'];
    public function receivable() { return $this->belongsTo(Receivable::class); }
}
