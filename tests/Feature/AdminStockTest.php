<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;

class AdminStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_product_stock_on_index()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        // create some stock
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 5, 'created_by' => $admin->id]);

        $this->actingAs($admin);

        $resp = $this->get(route('admin.products.index'));
        $resp->assertStatus(200);
        $resp->assertSee('Adjust');
        $resp->assertSee('5');
    }

    public function test_admin_can_add_stock_in()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        $this->actingAs($admin);

        $resp = $this->post(route('admin.products.stock.store', $product), [
            'type' => 'IN',
            'qty' => 10,
        ]);

        $resp->assertRedirect(route('admin.products.stock', $product));
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'IN',
            'qty' => 10,
        ]);

        $stock = app(StockService::class)->getAvailableStockForProduct($product->id);
        $this->assertEquals(10, $stock);
    }

    public function test_admin_cannot_create_out_movement_exceeding_available()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();

        $this->actingAs($admin);

        $resp = $this->post(route('admin.products.stock.store', $product), [
            'type' => 'OUT',
            'qty' => 3,
        ]);

        $resp->assertSessionHasErrors('qty');
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $product->id,
            'type' => 'OUT',
            'qty' => 3,
        ]);
    }

    public function test_non_admin_cannot_access_stock_routes()
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $this->actingAs($user);

        $resp = $this->get(route('admin.products.stock', $product));
        $resp->assertStatus(403);
    }
}
