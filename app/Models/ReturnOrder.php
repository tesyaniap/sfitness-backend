<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReturnOrder extends Model
{
    protected $table    = 'returns';
    protected $fillable = ['return_number','distributor_id','purchase_id','reason'];

    public function distributor() { return $this->belongsTo(Distributor::class); }
    public function purchase()    { return $this->belongsTo(Purchase::class); }
    public function items()       { return $this->hasMany(ReturnItem::class, 'return_id'); }

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($r) => $r->return_number = 'RTN-' . strtoupper(uniqid()));
    }
}
