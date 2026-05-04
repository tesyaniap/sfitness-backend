<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number', 'user_id', 'total_amount',
        'status', 'payment_method', 'snap_token',
        'midtrans_order_id', 'payment_type', 'notes',
    ];

    protected $casts = ['total_amount' => 'decimal:2'];

    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(TransactionItem::class); }
    public function salePayments() { return $this->hasMany(SalePayment::class); }
    public function receivable() { return $this->hasOne(Receivable::class); }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($transaction) {
            $transaction->invoice_number = 'INV-' . strtoupper(uniqid());
        });
    }
}
