<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Arr;
use Laravel\Sanctum\Sanctum;

class ApiTransactionAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_401()
    {
        $product = Product::factory()->create(['selling_price' => 10.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 5]);

        $payload = ['items' => [['product_id' => $product->id, 'qty' => 1]]];

        $resp = $this->postJson('/api/transactions', $payload);

        $resp->assertStatus(401);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_authenticated_request_with_token_creates_transaction()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 20.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 5]);

        Sanctum::actingAs($user);

        $payload = ['items' => [['product_id' => $product->id, 'qty' => 1]]];

        $resp = $this->postJson('/api/transactions', $payload);

        $resp->assertStatus(201);
        $resp->assertJsonStructure(['transaction_id', 'code', 'grand_total', 'status', 'transacted_at']);

        $transactionId = Arr::get($resp->json(), 'transaction_id');
        $this->assertDatabaseHas('transactions', ['id' => $transactionId, 'user_id' => $user->id]);
    }

    public function test_insufficient_stock_returns_422_and_rolls_back_even_with_token()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['selling_price' => 50.00]);

        Sanctum::actingAs($user);

        $payload = ['items' => [['product_id' => $product->id, 'qty' => 2]]];

        $resp = $this->postJson('/api/transactions', $payload);

        $resp->assertStatus(422);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $product->id, 'type' => 'OUT']);
    }
}
