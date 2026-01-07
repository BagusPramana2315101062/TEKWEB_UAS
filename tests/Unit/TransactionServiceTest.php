<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\TransactionService;
use App\Models\Product;
use App\Models\StockMovement;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_nominal_discount_and_tax_after_discount()
    {
        $product = Product::factory()->create(['selling_price' => 100.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        $service = $this->app->make(TransactionService::class);

        $trx = $service->createTransaction(null, [
            ['product_id' => $product->id, 'qty' => 2]
        ], ['discount_type' => null, 'discount_value' => 15.00, 'tax_rate' => 10]);

        // subtotal = 200
        // discount = 15 (nominal)
        // taxable = 185
        // tax = 18.5
        // grand_total = 200 - 15 + 18.5 = 203.5

        $this->assertEquals(200.00, (float) $trx->subtotal);
        $this->assertEquals(18.50, (float) $trx->tax_amount);
        $this->assertEquals(203.50, (float) $trx->grand_total);
    }

    public function test_percent_discount_and_tax_after_discount()
    {
        $product = Product::factory()->create(['selling_price' => 100.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        $service = $this->app->make(TransactionService::class);

        $trx = $service->createTransaction(null, [
            ['product_id' => $product->id, 'qty' => 2]
        ], ['discount_type' => 'PERCENT', 'discount_value' => 10, 'tax_rate' => 10]);

        // subtotal = 200
        // discount = 10% => 20
        // taxable = 180
        // tax = 18
        // grand_total = 200 - 20 + 18 = 198

        $this->assertEquals(200.00, (float) $trx->subtotal);
        $this->assertEquals(18.00, (float) $trx->tax_amount);
        $this->assertEquals(198.00, (float) $trx->grand_total);
    }
}