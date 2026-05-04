<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    protected $fillable = ['purchase_id','distributor_id','total_amount','paid_amount','remaining_amount','due_date','status','notes'];
    protected $casts    = ['due_date' => 'date', 'total_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'remaining_amount' => 'decimal:2'];

    public function purchase()    { return $this->belongsTo(Purchase::class); }
    public function distributor() { return $this->belongsTo(Distributor::class); }
    public function payments()    { return $this->hasMany(DebtPayment::class); }
}
