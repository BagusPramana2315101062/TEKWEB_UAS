<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class TransactionItemDiscountPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_percent_product_discount_is_saved_on_transaction_item()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['selling_price' => 50.00, 'discount_type' => 'PERCENT', 'discount_value' => 10]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        $payload = [ 'items' => [ ['product_id' => $product->id, 'qty' => 2] ] ];

        $response = $this->postJson('/api/transactions', $payload);
        $response->assertStatus(201);
        $json = $response->json();

        // per-line discount nominal should be 10% of line total (100) => 10
        $this->assertDatabaseHas('transaction_items', [ 'product_id' => $product->id, 'discount_type' => 'PERCENT', 'discount_nominal' => 10.00 ]);
    }

    public function test_nominal_product_discount_per_unit_is_saved_on_transaction_item()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['selling_price' => 105.50, 'discount_type' => 'NOMINAL', 'discount_value' => 5.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        $payload = [ 'items' => [ ['product_id' => $product->id, 'qty' => 3] ] ];

        $response = $this->postJson('/api/transactions', $payload);
        $response->assertStatus(201);
        $json = $response->json();

        // legacy NOMINAL is ignored for new transactions: discount_nominal should be 0
        $this->assertDatabaseHas('transaction_items', [ 'product_id' => $product->id, 'discount_type' => 'NOMINAL', 'discount_value' => 5.00, 'discount_nominal' => 0.00 ]);
    }
}
