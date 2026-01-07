<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id', 'product_id', 'qty', 'price', 'line_total',
        'discount_type', 'discount_value', 'discount_nominal',
    ];

    protected $casts = [
        'transaction_id' => 'integer',
        'product_id' => 'integer',
        'qty' => 'integer',
        'price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_nominal' => 'decimal:2',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
}
