<?php

namespace App\Observers;

use App\Models\StockMovement;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

class StockMovementObserver
{
    protected AuditService $audit;

    public function __construct()
    {
        $this->audit = app(AuditService::class);
    }

    public function created(StockMovement $movement)
    {
        $desc = strtoupper($movement->type) . " movement for product {$movement->product_id} (qty: {$movement->qty})";
        $this->audit->log(optional(Auth::user())->id, 'CREATE', get_class($movement), $movement->id, $desc, $movement->toArray());
    }

    public function deleted(StockMovement $movement)
    {
        $desc = "Deleted stock movement {$movement->id} for product {$movement->product_id}";
        $this->audit->log(optional(Auth::user())->id, 'DELETE', get_class($movement), $movement->id, $desc, $movement->toArray());
    }
}
