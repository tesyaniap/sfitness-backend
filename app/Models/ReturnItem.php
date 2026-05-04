<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    protected $table    = 'return_items';
    protected $fillable = ['return_id','product_id','quantity','price','subtotal'];
    protected $casts    = ['price' => 'decimal:2', 'subtotal' => 'decimal:2'];
    public function product()      { return $this->belongsTo(Product::class); }
    public function returnOrder()  { return $this->belongsTo(ReturnOrder::class, 'return_id'); }
}
