<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'type', 'qty', 'ref_type', 'ref_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'qty' => 'integer',
        'ref_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }
}
