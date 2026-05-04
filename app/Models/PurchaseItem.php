<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id','product_id','quantity','price','subtotal'];
    protected $casts    = ['price' => 'decimal:2', 'subtotal' => 'decimal:2'];
    public function product()  { return $this->belongsTo(Product::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
}
