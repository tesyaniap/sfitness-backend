<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'barcode', 'category_id', 'description', 'category',
        'price', 'stock', 'image', 'is_available',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function transactionItems() { return $this->hasMany(TransactionItem::class); }
    public function categoryRelation() { return $this->belongsTo(Category::class, 'category_id'); }
}
