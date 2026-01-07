<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

class ProductObserver
{
    protected AuditService $audit;

    protected array $whitelist = ['name', 'slug', 'purchase_price', 'selling_price', 'is_active'];

    public function __construct()
    {
        $this->audit = app(AuditService::class);
    }

    public function created(Product $product)
    {
        $this->audit->log(optional(Auth::user())->id, 'CREATE', get_class($product), $product->id, "Created product {$product->name}", $product->toArray());
    }

    public function updated(Product $product)
    {
        $before = $product->getOriginal();
        $after = $product->getAttributes();
        $changes = $this->audit->computeDiff($before, $after, $this->whitelist);

        $desc = "Updated product {$product->name}";
        $this->audit->log(optional(Auth::user())->id, 'UPDATE', get_class($product), $product->id, $desc, $changes);
    }

    public function deleted(Product $product)
    {
        $this->audit->log(optional(Auth::user())->id, 'DELETE', get_class($product), $product->id, "Deleted product {$product->name}", $product->toArray());
    }
}
