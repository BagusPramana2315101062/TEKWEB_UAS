<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\TaxSetting;
use App\Services\TransactionService;
use App\Services\StockService;
use App\Models\Product;

class TransactionTaxCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_is_computed_after_percent_discount_using_db_rate()
    {
        // Use DB tax rate of 20%
        TaxSetting::setRate(20.0);

        $product = Product::factory()->create(['selling_price' => 100]);
        \App\Models\StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'created_by' => null]);
        $stockService = $this->app->make(StockService::class);
        $service = new TransactionService($stockService);

        $trx = $service->createTransaction(null, [
            ['product_id' => $product->id, 'qty' => 1, 'price' => 100]
        ], ['discount_type' => 'PERCENT', 'discount_value' => 10]); // 10% discount -> 10.00

        // subtotal 100, discount 10 -> taxable base 90, tax @20% = 18
        $this->assertEquals(100.00, $trx->subtotal);
        $this->assertEquals(10.00, $trx->discount_nominal);
        $this->assertEquals(18.00, $trx->tax_amount);
        $this->assertEquals(108.00, $trx->grand_total);
    }

    public function test_tax_is_computed_after_nominal_discount_using_db_rate()
    {
        TaxSetting::setRate(10.0);

        $product = Product::factory()->create(['selling_price' => 50]);
        \App\Models\StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'created_by' => null]);
        $stockService = $this->app->make(StockService::class);
        $service = new TransactionService($stockService);

        $trx = $service->createTransaction(null, [
            ['product_id' => $product->id, 'qty' => 2, 'price' => 50]
        ], ['discount_type' => 'NOMINAL', 'discount_value' => 5.0]); // subtotal 100, discount 5

        // taxable base 95, tax @10% = 9.50
        $this->assertEquals(100.00, $trx->subtotal);
        $this->assertEquals(5.00, $trx->discount_nominal);
        $this->assertEquals(9.50, $trx->tax_amount);
        $this->assertEquals(104.50, $trx->grand_total);
    }
}
