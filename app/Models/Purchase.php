<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = ['purchase_number','distributor_id','total_amount','payment_method','status','due_date','notes'];
    protected $casts    = ['due_date' => 'date', 'total_amount' => 'decimal:2'];

    public function distributor() { return $this->belongsTo(Distributor::class); }
    public function items()       { return $this->hasMany(PurchaseItem::class); }
    public function debt()        { return $this->hasOne(Debt::class); }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($p) {
            $p->purchase_number = 'PO-' . strtoupper(uniqid());
        });
    }
}
