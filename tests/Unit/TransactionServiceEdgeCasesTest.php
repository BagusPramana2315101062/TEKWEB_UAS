<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\TransactionService;
use App\Models\Product;
use App\Models\StockMovement;

class TransactionServiceEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_100_percent_discount_results_zero_total_and_zero_tax()
    {
        $product = Product::factory()->create(['selling_price' => 50.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        config(['tax.default_rate' => 10]);

        $service = $this->app->make(TransactionService::class);

        $trx = $service->createTransaction(null, [['product_id' => $product->id, 'qty' => 2]], ['discount_type' => 'PERCENT', 'discount_value' => 100]);

        $this->assertEquals(100.00, (float)$trx->subtotal);
        $this->assertEquals(100.00, (float)$trx->discount_nominal);
        $this->assertEquals(0.00, (float)$trx->tax_amount);
        $this->assertEquals(0.00, (float)$trx->grand_total);
    }

    public function test_discount_greater_than_subtotal_is_clamped()
    {
        $product = Product::factory()->create(['selling_price' => 30.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        config(['tax.default_rate' => 10]);

        $service = $this->app->make(TransactionService::class);

        $trx = $service->createTransaction(null, [['product_id' => $product->id, 'qty' => 2]], ['discount_type' => null, 'discount_value' => 1000]);

        $this->assertEquals(60.00, (float)$trx->subtotal);
        $this->assertEquals(60.00, (float)$trx->discount_nominal);
        $this->assertEquals(0.00, (float)$trx->tax_amount);
        $this->assertEquals(0.00, (float)$trx->grand_total);
    }

    public function test_negative_discount_is_clamped_to_zero()
    {
        $product = Product::factory()->create(['selling_price' => 20.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        config(['tax.default_rate' => 10]);

        $service = $this->app->make(TransactionService::class);

        $trx = $service->createTransaction(null, [['product_id' => $product->id, 'qty' => 3]], ['discount_type' => null, 'discount_value' => -50]);

        $this->assertEquals(60.00, (float)$trx->subtotal);
        $this->assertEquals(0.00, (float)$trx->discount_nominal);
        $this->assertEquals(6.00, (float)$trx->tax_amount); // 10% of 60
        $this->assertEquals(66.00, (float)$trx->grand_total);
    }
}
