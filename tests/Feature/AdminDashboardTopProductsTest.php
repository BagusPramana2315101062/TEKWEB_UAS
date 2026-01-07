<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\TransactionService;

class AdminDashboardTopProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_products_table_shows_products_after_transactions()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $productA = Product::factory()->create(['name' => 'Product A', 'selling_price' => 50.00]);
        $productB = Product::factory()->create(['name' => 'Product B', 'selling_price' => 30.00]);

        StockMovement::create([ 'product_id' => $productA->id, 'type' => 'IN', 'qty' => 5, 'notes' => 'stock']);
        StockMovement::create([ 'product_id' => $productB->id, 'type' => 'IN', 'qty' => 5, 'notes' => 'stock']);

        $this->app->make(TransactionService::class)->createTransaction($admin->id, [[ 'product_id' => $productA->id, 'qty' => 2 ]]);
        $this->app->make(TransactionService::class)->createTransaction($admin->id, [[ 'product_id' => $productB->id, 'qty' => 1 ]]);

        $resp = $this->get('/admin/dashboard');
        $resp->assertStatus(200);
        $resp->assertSee('Top Products');
        $resp->assertSee('Product A');
        $resp->assertSee('Product B');
    }

    public function test_empty_top_products_shows_message()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $resp = $this->get('/admin/dashboard');
        $resp->assertStatus(200);
        $resp->assertSee('Tidak ada produk terjual', false);
    }
}