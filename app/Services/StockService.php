<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use App\Exceptions\InsufficientStockException;

class StockService
{
    public function getAvailableStockForProduct(int $productId): int
    {
        $in = StockMovement::where('product_id', $productId)->where('type', 'IN')->sum('qty');
        $out = StockMovement::where('product_id', $productId)->where('type', 'OUT')->sum('qty');
        return (int) ($in - $out);
    }

    public function validateStockAvailability(array $items): void
    {
        $shortages = [];

        $productIds = collect($items)->pluck('product_id')->unique()->values()->all();

        // lock products for update inside transaction
        $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

        // aggregate stock movements in one query to avoid N+1 queries
        $movements = StockMovement::whereIn('product_id', $productIds)
            ->select('product_id', 
                DB::raw("SUM(CASE WHEN type = 'IN' THEN qty ELSE 0 END) as in_qty"),
                DB::raw("SUM(CASE WHEN type = 'OUT' THEN qty ELSE 0 END) as out_qty")
            )
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        foreach ($items as $it) {
            $pid = $it['product_id'];
            $req = (int) $it['qty'];

            $agg = $movements->get($pid);
            $in = $agg ? (int) $agg->in_qty : 0;
            $out = $agg ? (int) $agg->out_qty : 0;
            $available = $in - $out;

            if ($available < $req) {
                $shortages[] = [
                    'product_id' => $pid,
                    'available' => $available,
                    'requested' => $req,
                ];
            }
        }

        if (!empty($shortages)) {
            throw new InsufficientStockException($shortages);
        }
    }

    public function createStockMovement(array $data): StockMovement
    {
        return StockMovement::create($data);
    }
}
