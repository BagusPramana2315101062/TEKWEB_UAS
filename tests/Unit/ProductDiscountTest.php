<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\TaxSetting;

class ProductDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_percent_discount_applies_to_transaction()
    {
        TaxSetting::setRate(10.0);

        $product = Product::factory()->create(['selling_price' => 100.00, 'discount_type' => 'PERCENT', 'discount_value' => 10]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10]);

        $service = $this->app->make(\App\Services\TransactionService::class);
        $trx = $service->createTransaction(null, [['product_id' => $product->id, 'qty' => 1]]);

        $this->assertEquals(10.00, (float) $trx->discount_nominal);
        // subtotal 100, discount 10, taxable 90, tax @10% = 9, grand_total = 99
        $this->assertEquals(99.00, (float) $trx->grand_total);
    }

    public function test_product_nominal_discount_per_unit_applies_to_transaction()
    {
        TaxSetting::setRate(10.0);

        $product = Product::factory()->create(['selling_price' => 100.00, 'discount_type' => 'NOMINAL', 'discount_value' => 5.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10]);

        $service = $this->app->make(\App\Services\TransactionService::class);
        $trx = $service->createTransaction(null, [['product_id' => $product->id, 'qty' => 3]]);

        // legacy NOMINAL is ignored for new transactions
        $this->assertEquals(0.00, (float) $trx->discount_nominal);
        // subtotal 300, no discount, tax @10% = 30, grand_total = 330
        $this->assertEquals(330.00, (float) $trx->grand_total);
    }
}
