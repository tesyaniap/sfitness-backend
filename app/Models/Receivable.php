<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    protected $fillable = ['transaction_id','user_id','total_amount','paid_amount','remaining_amount','due_date','status','notes'];
    protected $casts    = ['due_date' => 'date', 'total_amount' => 'decimal:2', 'paid_amount' => 'decimal:2', 'remaining_amount' => 'decimal:2'];

    public function transaction() { return $this->belongsTo(Transaction::class); }
    public function user()        { return $this->belongsTo(User::class); }
    public function payments()    { return $this->hasMany(ReceivablePayment::class); }
}
