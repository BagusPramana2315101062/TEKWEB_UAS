<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Arr;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ApiTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_transaction_creates_transaction_and_stock_movements()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['selling_price' => 100.00]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'IN',
            'qty' => 10,
            'notes' => 'Initial stock',
        ]);

        $payload = [
            'items' => [
                ['product_id' => $product->id, 'qty' => 2]
            ],
            'tax_rate' => 0,
            'discount_value' => 0,
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['transaction_id','code','grand_total','status','transacted_at','items']);

        $json = $response->json();
        $this->assertCount(1, $json['items']);
        $this->assertEquals(2, $json['items'][0]['qty']);

        $transactionId = Arr::get($response->json(), 'transaction_id');

        $this->assertDatabaseHas('transactions', ['id' => $transactionId]);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transactionId,
            'product_id' => $product->id,
            'qty' => 2,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'OUT',
            'qty' => 2,
            'ref_type' => 'transaction',
            'ref_id' => $transactionId,
        ]);

        $in = StockMovement::where('product_id', $product->id)->where('type', 'IN')->sum('qty');
        $out = StockMovement::where('product_id', $product->id)->where('type', 'OUT')->sum('qty');
        $this->assertEquals(8, $in - $out);
    }

    public function test_api_transaction_fails_and_rolls_back_on_insufficient_stock()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['selling_price' => 50.00]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'IN',
            'qty' => 1,
            'notes' => 'Initial stock',
        ]);

        $payload = [
            'items' => [
                ['product_id' => $product->id, 'qty' => 2]
            ],
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(422)
            ->assertJsonStructure(['message','items']);

        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $product->id,
            'type' => 'OUT',
        ]);
    }
}
