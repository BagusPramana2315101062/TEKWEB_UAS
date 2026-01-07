<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\StockService;
use App\Models\Product;
use App\Models\StockMovement;
use App\Exceptions\InsufficientStockException;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_stock_availability_passes_when_enough_stock()
    {
        $product = Product::factory()->create();

        // add stock movements: IN 10, OUT 3 => available 7
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'OUT', 'qty' => 3]);

        $svc = new StockService();

        // should not throw
        $svc->validateStockAvailability([['product_id' => $product->id, 'qty' => 5]]);

        $this->assertTrue(true);
    }

    public function test_validate_stock_availability_throws_when_not_enough_stock()
    {
        $this->expectException(InsufficientStockException::class);

        $product = Product::factory()->create();

        // add stock movements: IN 2, OUT 0 => available 2
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 2]);

        $svc = new StockService();

        $svc->validateStockAvailability([['product_id' => $product->id, 'qty' => 5]]);
    }
}
