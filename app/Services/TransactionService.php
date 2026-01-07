<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function createTransaction(?int $userId, array $items, array $meta = []): Transaction
    {
        $productIds = collect($items)->pluck('product_id')->unique()->values()->all();
        $products = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

        $normalized = [];
        foreach ($items as $it) {
            $p = $products->get($it['product_id']);
            if (!$p) {
                throw new \InvalidArgumentException("Product {$it['product_id']} not found.");
            }
            $price = isset($it['price']) ? (float) $it['price'] : (float) $p->selling_price;
            $qty = (int) $it['qty'];
            $lineTotal = round($price * $qty, 2);

            // determine item-level discount (override -> product default -> none)
            $itemDiscountType = $it['discount_type'] ?? $p->discount_type ?? null;
            $itemDiscountValue = isset($it['discount_value']) ? $it['discount_value'] : ($p->discount_value ?? 0.00);

            // compute per-line nominal discount immediately so we can persist it later
            $itemNominal = 0.00;
            if ($itemDiscountType === 'PERCENT') {
                $pct = (float) $itemDiscountValue;
                if ($pct < 0) $pct = 0.0;
                if ($pct > 100) $pct = 100.0;
                $itemNominal = round($lineTotal * ($pct / 100), 2);
            } else {
                // NOMINAL is intentionally ignored for new transactions; legacy NOMINAL values are persisted but not applied to calculations
                $itemNominal = 0.00;
            }

            if ($itemNominal < 0) $itemNominal = 0.0;
            if ($itemNominal > $lineTotal) $itemNominal = $lineTotal;

            $normalized[] = [
                'product' => $p,
                'product_id' => $p->id,
                'qty' => $qty,
                'price' => $price,
                'line_total' => $lineTotal,
                'item_discount_type' => $itemDiscountType,
                'item_discount_value' => $itemDiscountValue,
                'item_nominal_discount' => $itemNominal,
            ];
        }

        return DB::transaction(function () use ($userId, $normalized, $meta) {

            $this->stockService->validateStockAvailability(array_map(function ($n) {
                return ['product_id' => $n['product_id'], 'qty' => $n['qty']];
            }, $normalized));

            $subtotal = array_sum(array_column($normalized, 'line_total'));
            $discount_type = $meta['discount_type'] ?? null;
            $discount_value = $meta['discount_value'] ?? 0.00;
            // Use configured tax rate from DB (admin sets it in settings)
            $tax_rate = \App\Models\TaxSetting::getRate();

            // compute product-level discounts (sum of per-item discounts)
            $productLevelDiscount = array_sum(array_column($normalized, 'item_nominal_discount'));

            // Normalize discount (transaction-level meta): support PERCENT and NOMINAL types.
            $metaDiscountNominal = 0.00;
            if ($discount_type === 'PERCENT') {
                $percent = (float) $discount_value;
                // clamp percent between 0 and 100
                if ($percent < 0) $percent = 0.0;
                if ($percent > 100) $percent = 100.0;
                // apply percent on subtotal after product-level discounts
                $metaDiscountNominal = round(($subtotal - $productLevelDiscount) * ($percent / 100), 2);
            } else {
                $metaDiscountNominal = round((float) $discount_value, 2);
            }

            // total discount is product-level + meta-level, clamped to subtotal
            $discountNominal = $productLevelDiscount + $metaDiscountNominal;
            if ($discountNominal < 0) $discountNominal = 0.0;
            if ($discountNominal > $subtotal) $discountNominal = $subtotal;

            // Tax is computed AFTER discount (business rule) on (subtotal - discountNominal)
            $taxableBase = max(0, $subtotal - $discountNominal);
            $tax_amount = round($taxableBase * ((float)$tax_rate / 100), 2);

            $grand_total = round($subtotal - $discountNominal + $tax_amount, 2);

            $trx = Transaction::create([
                'user_id' => $userId,
                'code' => $this->generateCode(),
                'subtotal' => $subtotal,
                'discount_type' => $discount_type,
                'discount_value' => $discount_value,
                'tax_rate' => $tax_rate,'discount_nominal' => $discountNominal,
                'tax_amount' => $tax_amount,
                'grand_total' => $grand_total,
                'status' => $meta['status'] ?? 'PAID',
                'transacted_at' => Carbon::now(),
            ]);

            foreach ($normalized as $n) {
                TransactionItem::create([
                    'transaction_id' => $trx->id,
                    'product_id' => $n['product_id'],
                    'qty' => $n['qty'],
                    'price' => $n['price'],
                    'line_total' => $n['line_total'],
                    'discount_type' => $n['item_discount_type'] ?? null,
                    'discount_value' => $n['item_discount_value'] ?? 0.00,
                    'discount_nominal' => $n['item_nominal_discount'] ?? 0.00,
                ]);

                $this->stockService->createStockMovement([
                    'product_id' => $n['product_id'],
                    'type' => 'OUT',
                    'qty' => $n['qty'],
                    'ref_type' => 'transaction',
                    'ref_id' => $trx->id,
                    'notes' => 'Sold via transaction ' . $trx->code,
                    'created_by' => $userId,
                ]);
            }

            return $trx;
        });
    }

    public function buildTransactionsQuery(array $filters = [])
    {
        $query = Transaction::with('user');

        // search by code, or user name/email
        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                  ->orWhereHas('user', function ($uq) use ($term) {
                      $uq->where('name', 'like', "%{$term}%")
                         ->orWhere('email', 'like', "%{$term}%");
                  });
            });
        }

        // status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // date range
        if (!empty($filters['date_from'])) {
            $query->whereDate('transacted_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('transacted_at', '<=', $filters['date_to']);
        }

        // amount range
        if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
            $query->where('grand_total', '>=', (float) $filters['min_amount']);
        }
        if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
            $query->where('grand_total', '<=', (float) $filters['max_amount']);
        }

        // sorting
        $allowedSort = ['transacted_at','grand_total','id'];
        $sortBy = in_array($filters['sort_by'] ?? 'transacted_at', $allowedSort) ? ($filters['sort_by'] ?? 'transacted_at') : 'transacted_at';
        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        return $query;
    }

    public function paginateTransactions(int $perPage = 15, array $filters = [])
    {
        $query = $this->buildTransactionsQuery($filters);

        $paginator = $query->paginate($perPage);
        $paginator->appends($filters);

        return $paginator;
    }

    public function getTransactionById(int $id): ?Transaction
    {
        return Transaction::with(['items.product', 'user'])->find($id);
    }

    protected function generateCode(): string
    {
        return 'TRX-' . strtoupper(Str::random(8));
    }
}
