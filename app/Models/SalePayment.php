<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    protected $fillable = ['transaction_id','method','amount'];
    protected $casts    = ['amount' => 'decimal:2'];
    public function transaction() { return $this->belongsTo(Transaction::class); }
}
